<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\TypeOtp;
use App\Models\Auth\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class GenerateMagicLinkAction
{
    public function execute(string $email): string
    {
        // Verificar se o usuário existe
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Ainda geramos um link, mesmo que o usuário não exista,
            // para evitar enumeração de usuários, mas o link não funcionará
            return $this->generateFakeLink();
        }

        return $this->generateRealLink($email);
    }

    private function generateRealLink(string $email): string
    {
        // Limpar códigos anteriores para este email
        OtpCode::where('email', $email)
            ->where('type', TypeOtp::MAGIC_LINK)
            ->delete();

        // Gerar código único
        $token = Str::random(64);

        // Salvar no banco
        OtpCode::create([
            'email'      => $email,
            'code'       => $token,
            'type'       => TypeOtp::MAGIC_LINK,
            'expires_at' => now()->addMinutes(30),
        ]);

        // Gerar URL assinada
        return URL::temporarySignedRoute(
            'auth.magic-link.verify',
            now()->addMinutes(30),
            ['token' => $token, 'email' => $email]
        );
    }

    private function generateFakeLink(): string
    {
        return URL::temporarySignedRoute(
            'auth.magic-link.verify',
            now()->addMinutes(30),
            ['token' => Str::random(64), 'email' => 'unknown@example.com']
        );
    }
}
