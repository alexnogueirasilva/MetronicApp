<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Models\Auth\OtpCode;
use App\Models\User;

readonly class VerifyOtpCodeAction
{
    public function __construct(
        private TotpManagerAction $manager,
    ) {}

    public function viaEmail(string $email, string $code): bool
    {
        $otp = OtpCode::query()
            ->where('email', $email)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return false;
        }

        $otp->forceFill(['used' => true])->save();

        return true;
    }

    public function viaTotp(User $user, string $code): bool
    {
        return $user->totp_secret && $this->manager->verify($user->totp_secret, $code);
    }
}
