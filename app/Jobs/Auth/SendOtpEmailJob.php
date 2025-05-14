<?php declare(strict_types = 1);

namespace App\Jobs\Auth;

use App\Mail\Auth\OtpCodeMail;
use App\Models\Auth\OtpCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOtpEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public OtpCode $otp,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->otp->email)->send(new OtpCodeMail($this->otp));
    }
}
