<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\TypeOtp;
use App\Models\Auth\OtpCode;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Action para gerar URLs de magic link
 *
 * Esta classe tem a responsabilidade única de gerar um link de autenticação
 * que pode ser enviado por email ao usuário.
 */
class GenerateMagicLinkAction
{
    /**
     * Gera um magic link para o email especificado
     */
    public function execute(string $email): string
    {
        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            return $this->generateFakeLink();
        }

        return $this->generateRealLink($email);
    }

    /**
     * Gera um link falso para usuários não existentes (prevenção de enumeração)
     */
    private function generateFakeLink(): string
    {
        $token   = Str::random(64);
        $email   = 'unknown@example.com';
        $expires = now()->addMinutes(30)->timestamp;

        $appKey       = config('app.key');
        $appKeyString = is_string($appKey) ? $appKey : '';

        $signature = hash_hmac('sha256', $email . $token . $expires, $appKeyString);

        return route('auth.magic-link.verify.get', [
            'token'     => $token,
            'email'     => $email,
            'expires'   => $expires,
            'signature' => $signature,
        ]);
    }

    /**
     * Gera um link real para usuários existentes
     */
    private function generateRealLink(string $email): string
    {
        OtpCode::query()->where('email', $email)
            ->where('type', TypeOtp::MAGIC_LINK)
            ->delete();

        $token   = Str::random(64);
        $expires = now()->addMinutes(30)->timestamp;

        $appKey       = config('app.key');
        $appKeyString = is_string($appKey) ? $appKey : '';

        OtpCode::query()->create([
            'email'      => $email,
            'code'       => $token,
            'type'       => TypeOtp::MAGIC_LINK,
            'expires_at' => now()->addMinutes(30),
        ]);

        $signature = hash_hmac('sha256', $email . $token . $expires, $appKeyString);

        return route('auth.magic-link.verify.get', [
            'token'     => $token,
            'email'     => $email,
            'expires'   => $expires,
            'signature' => $signature,
        ]);
    }
}
