<?php declare(strict_types = 1);

namespace App\Listeners;

use App\Events\ImpersonationActionPerformed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use OwenIt\Auditing\Models\Audit;

class ImpersonationAuditListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public $backoff = [5, 15, 30];

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ImpersonationActionPerformed $event): void
    {
        Audit::create([
            'user_type'      => $event->user::class,
            'user_id'        => $event->user->id,
            'event'          => 'impersonated-action',
            'auditable_type' => $event->user::class,
            'auditable_id'   => $event->user->id,
            'old_values'     => [],
            'new_values'     => [
                'impersonator_id'  => $event->impersonation->impersonator_id,
                'impersonation_id' => $event->impersonation->id,
                'action'           => $event->action,
                'url'              => $event->url,
                'ip'               => $event->ip,
            ],
            'ip_address' => $event->ip,
            'user_agent' => $event->userAgent,
        ]);
    }
}
