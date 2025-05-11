<?php declare(strict_types = 1);

namespace App\Jobs\Auth;

use App\Actions\GeoLocationAction\GeoLocationAction;
use App\Mail\Auth\PasswordChangedMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\{Mail};

class SendPasswordChangedNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public string $ip,
        public ?string $userAgent,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GeoLocationAction $action): void
    {
        $geo = $action->lookup($this->ip);

        Mail::to($this->user->email)->send(
            new PasswordChangedMail(
                ip: $geo->ip,
                city: $geo->city,
                country: $geo->country,
                userAgent: $this->userAgent ?? 'Não informado'
            )
        );
    }
}
