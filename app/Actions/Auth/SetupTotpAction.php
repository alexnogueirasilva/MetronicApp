<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\DTO\Auth\TotpSecretDTO;
use App\Enums\TypeOtp;
use App\Models\User;

readonly class SetupTotpAction
{
    public function __construct(
        private TotpManagerAction $totpManager,
    ) {}

    public function __invoke(User $user): TotpSecretDTO
    {
        $secret = $this->totpManager->generate($user->email);

        $user->forceFill([
            'totp_secret' => $secret->secret,
            'otp_method'  => TypeOtp::TOTP,
        ])->save();

        return $secret;
    }
}
