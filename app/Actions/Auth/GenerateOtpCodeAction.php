<?php
declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Jobs\Auth\SendOtpEmailJob;
use App\Models\Auth\OtpCode;
use Random\RandomException;

class GenerateOtpCodeAction
{
    /**
     * @throws RandomException
     */
    public function __invoke(string $email): void
    {
        $code = (string) random_int(100000, 999999);

        $otp = new OtpCode();
        $otp->forceFill([
            'email'      => $email,
            'code'       => $code,
            'type'       => 'email',
            'expires_at' => now()->addMinutes(10),
            'used'       => false,
        ])->save();

        SendOtpEmailJob::dispatch($otp);
    }
}
