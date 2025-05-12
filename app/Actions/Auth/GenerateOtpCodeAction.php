<?php
declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\QueueEnum;
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

        // OTP via email é uma autenticação crítica, merece prioridade alta
        SendOtpEmailJob::dispatch($otp)
            ->onQueue(QueueEnum::AUTH_CRITICAL->value);
    }
}
