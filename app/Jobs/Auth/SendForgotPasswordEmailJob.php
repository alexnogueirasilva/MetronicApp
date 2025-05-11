<?php declare(strict_types = 1);

namespace App\Jobs\Auth;

use App\Mail\Auth\ForgotPasswordMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;
use Mail;

class SendForgotPasswordEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $token = Password::broker()->createToken($this->user);

        Mail::to($this->user->email)->send(new ForgotPasswordMail($this->user->email, $token));
    }
}
