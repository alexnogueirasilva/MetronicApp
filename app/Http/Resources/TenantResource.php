<?php declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'name'                    => $this->name,
            'domain'                  => $this->domain,
            'plan'                    => $this->plan->value,
            'plan_name'               => ucfirst(strtolower($this->plan->name)),
            'is_active'               => $this->is_active,
            'on_trial'                => $this->onTrial(),
            'trial_ends_at'           => $this->whenHas('trial_ends_at', fn () => $this->trial_ends_at?->format('Y-m-d H:i:s')),
            'settings'                => $this->settings,
            'rate_limit'              => $this->getRateLimitPerMinute(),
            'max_concurrent_requests' => $this->getMaxConcurrentRequests(),
            'created_at'              => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'              => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
