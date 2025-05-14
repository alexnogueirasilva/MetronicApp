<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Traits\Auth\AuthenticatedUser;

class DisableOtpAction
{
    use AuthenticatedUser;
    /**
     * Create a new class instance.
     */
    public function execute(): void
    {
        $user = $this->getAuthenticatedUser();

        $user->forceFill([
            'otp_method'    => null,
            'totp_secret'   => null,
            'totp_verified' => false,
        ])->save();
    }
}
