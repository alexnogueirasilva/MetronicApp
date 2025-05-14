<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\{Horizon, HorizonApplicationServiceProvider};

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Configurar notificações para e-mail, SMS ou Slack
        $notificationEmail = is_string(config('horizon.notifications.email'))
            ? config('horizon.notifications.email')
            : 'admin@example.com';

        Horizon::routeMailNotificationsTo($notificationEmail);

        // Se tiver Slack configurado
        $slackWebhook = config('horizon.notifications.slack_webhook');

        if (is_string($slackWebhook) && $slackWebhook !== '') {
            $slackChannel = is_string(config('horizon.notifications.slack_channel'))
                ? config('horizon.notifications.slack_channel')
                : '#horizon-notifications';

            Horizon::routeSlackNotificationsTo($slackWebhook, $slackChannel);
        }

        // Ativar o tema noturno por padrão
        Horizon::night();

        // Definir quando enviar notificações
        Horizon::auth(function (Request $request): bool {
            // Autoriza apenas requests internos (da mesma máquina) ou autenticados
            if (app()->environment('local')) {
                return true;
            }

            if ($request->ip() === '127.0.0.1') {
                return true;
            }

            return Gate::check('viewHorizon', [$request->user()]);
        });

        // No Horizon 5.x, as tags são definidas diretamente nos jobs através do método tags()
        // Não é mais necessário configurar no ServiceProvider
        // Horizon automaticamente detecta modelos Eloquent e adiciona tags com base neles
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (User $user): bool => // Verificar se o usuário tem permissão para ver o Horizon
            in_array($user->email, $this->getHorizonAdminEmails(), true) ||
               $user->hasRole('admin'));
    }

    /**
     * Obter lista de emails com permissão de acesso ao Horizon.
     *
     * @return array<string>
     */
    private function getHorizonAdminEmails(): array
    {
        $emails = config('horizon.admin_emails', 'admin@example.com');

        if (empty($emails)) {
            return ['admin@example.com'];
        }

        if (is_string($emails)) {
            return explode(',', $emails);
        }

        if (is_array($emails)) {
            $stringEmails = [];

            foreach ($emails as $email) {
                if (is_string($email)) {
                    $stringEmails[] = $email;
                }
            }

            return $stringEmails;
        }

        return ['admin@example.com'];
    }
}
