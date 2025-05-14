<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\TypeOtp;
use App\Models\Auth\OtpCode;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\{Auth, Log};

class VerifyMagicLinkAction
{
    /**
     * Verifica se a assinatura do magic link é válida
     */
    public function verifySignature(string $email, string $token, int $expires, string $signature): bool
    {
        if ($expires < now()->timestamp) {
            Log::warning('Magic link expirado', ['expires' => $expires]);

            return false;
        }

        $appKey       = config('app.key');
        $appKeyString = is_string($appKey) ? $appKey : '';

        $expectedSignature = hash_hmac('sha256', $email . $token . $expires, $appKeyString);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Magic link com assinatura inválida', [
                'expectedSignature' => $expectedSignature,
                'receivedSignature' => $signature,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Verifica o token OTP no banco de dados e autentica o usuário
     *
     * @throws AuthenticationException Se o token for inválido ou o usuário não existir
     * @return User O usuário autenticado
     */
    public function verifyTokenAndAuthenticate(string $email, string $token): User
    {
        $otpCode = OtpCode::query()
            ->where('code', $token)
            ->where('email', $email)
            ->where('type', TypeOtp::MAGIC_LINK)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpCode) {
            Log::warning('Magic link inválido ou expirado', ['token' => $token]);

            throw new AuthenticationException('Link inválido ou expirado');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            Log::warning('Tentativa de login com magic link para usuário inexistente', ['email' => $email]);

            throw new AuthenticationException('Usuário não encontrado');
        }

        Auth::login($user);

        $otpCode->delete();

        return $user;
    }

    /**
     * Gera um token de API para o usuário autenticado
     */
    public function generateAuthToken(User $user): string
    {
        return $user->createToken('magic-link-' . now()->timestamp)->plainTextToken;
    }
}
