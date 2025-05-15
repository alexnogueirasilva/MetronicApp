<?php declare(strict_types = 1);

namespace App\Models;

use App\Enums\PlanType;
use Database\Factories\TenantFactory;
use DevactionLabs\FilterablePackage\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $domain
 * @property PlanType $plan
 * @property bool $is_active
 * @property Carbon|null $trial_ends_at
 * @property array<string, mixed>|null $settings
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User[] $users
 * @property-read FeatureFlag[] $featureFlags
 *
 * @method static TenantFactory factory(...$parameters)
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;
    use Filterable;

    protected $guarded = [];

    /**
     * Get the users for the tenant.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the feature flags associated with this tenant.
     *
     * @return BelongsToMany<FeatureFlag>
     */
    // @phpstan-ignore-next-line
    public function featureFlags(): BelongsToMany
    {
        return $this->belongsToMany(FeatureFlag::class)->withPivot('value', 'expires_at');
    }

    /**
     * Verifica se o tenant está no período de trial
     */
    public function onTrial(): bool
    {
        if ($this->trial_ends_at === null) {
            return false;
        }

        /** @var Carbon $trialDate */
        $trialDate = $this->trial_ends_at;

        return $trialDate->isFuture();
    }

    /**
     * Retorna o limite de requisições por minuto para este tenant com base no plano
     */
    public function getRateLimitPerMinute(): int
    {
        if (isset($this->settings['custom_rate_limit'])) {
            /** @var int|string|mixed $value */
            $value = $this->settings['custom_rate_limit'];

            return toInteger($value);
        }

        return $this->plan->requestsPerMinute();
    }

    /**
     * Retorna o máximo de requisições simultâneas para este tenant
     */
    public function getMaxConcurrentRequests(): int
    {
        if (isset($this->settings['max_concurrent_requests'])) {
            /** @var int|string|mixed $value */
            $value = $this->settings['max_concurrent_requests'];

            return toInteger($value);
        }

        return $this->plan->maxConcurrentRequests();
    }

    /**
     * Retorna a chave a ser usada para cache de rate limiting
     */
    public function getRateLimitCacheKey(): string
    {
        return "tenant:{$this->id}:ratelimit";
    }

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'trial_ends_at' => 'datetime',
            'settings'      => 'array',
            'plan'          => PlanType::class,
        ];
    }
}
