<?php declare(strict_types = 1);

namespace App\Providers;

use App\Events\ImpersonationActionPerformed;
use App\Listeners\Horizon\{HandleJobFailed, HandleLongWaitDetected};
use App\Listeners\ImpersonationAuditListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Horizon\Events\{JobFailed, LongWaitDetected};
use Override;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        LongWaitDetected::class => [
            HandleLongWaitDetected::class,
        ],

        JobFailed::class => [
            HandleJobFailed::class,
        ],

        ImpersonationActionPerformed::class => [
            ImpersonationAuditListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    #[Override]
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    #[Override]
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
