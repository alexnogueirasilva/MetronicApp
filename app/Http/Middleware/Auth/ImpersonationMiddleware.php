<?php declare(strict_types = 1);

namespace App\Http\Middleware\Auth;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OwenIt\Auditing\Models\Audit;
use Symfony\Component\HttpFoundation\Response;

class ImpersonationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Se o usuário não está autenticado, pular o middleware
        if (!$user) {
            return $next($request);
        }

        /** @var User $user */
        // Verificar se o token atual é de impersonation
        $isImpersonated = $request->bearerToken() && $user->tokens()
            ->where('token', hash('sha256', $request->bearerToken()))
            ->where('name', 'impersonation-token')
            ->exists();

        if ($isImpersonated) {
            // Verificar se a impersonation ainda está ativa
            $impersonation = $user->activeBeingImpersonated();

            if (!$impersonation) {
                // Se a impersonation foi encerrada, revogar o token
                $user->tokens()->where('name', 'impersonation-token')->delete();

                return response()->json([
                    'message' => 'Sessão de impersonation encerrada pelo impersonator.',
                ], 401);
            }

            // Adicionar informações de impersonation ao request para uso posterior
            $request->attributes->add([
                'is_impersonated'  => true,
                'impersonator_id'  => $impersonation->impersonator_id,
                'impersonation_id' => $impersonation->id,
            ]);

            // Registrar a ação no log de auditoria
            Audit::create([
                'user_type'      => $user::class,
                'user_id'        => $user->id,
                'event'          => 'impersonated-action',
                'auditable_type' => $user::class,
                'auditable_id'   => $user->id,
                'old_values'     => [],
                'new_values'     => [
                    'impersonator_id'  => $impersonation->impersonator_id,
                    'impersonation_id' => $impersonation->id,
                    'action'           => $request->method(),
                    'url'              => $request->fullUrl(),
                    'ip'               => $request->ip(),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $next($request);
    }
}
