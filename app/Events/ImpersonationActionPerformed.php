<?php declare(strict_types = 1);

namespace App\Events;

use App\Models\{Impersonation, User};
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ImpersonationActionPerformed
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public User $user,
        public Impersonation $impersonation,
        public string $action,
        public string $url,
        public string $ip,
        public ?string $userAgent
    ) {}
}
