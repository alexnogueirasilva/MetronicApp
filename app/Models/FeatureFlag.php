<?php declare(strict_types = 1);

namespace App\Models;

use App\Enums\FeatureFlagType;
use Database\Factories\FeatureFlagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id ULID identifier
 * @property string $feature_name
 * @property string $display_name
 * @property string|null $description
 * @property FeatureFlagType $type
 * @property array<string, mixed>|null $parameters
 * @property bool $default_value
 * @property bool $is_active
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Tenant[] $tenants
 * @property-read User[] $users
 *
 * @method static FeatureFlagFactory factory(...$parameters)
 */
class FeatureFlag extends Model
{
    /** @use HasFactory<FeatureFlagFactory> */
    use HasFactory;

    protected $table = 'feature_flag_metadata';

    protected $guarded = [];

    /**
     * Get the tenants associated with this feature flag.
     *
     * @return BelongsToMany<Tenant, Model>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'feature_flag_tenant', 'feature_name', 'tenant_id', 'feature_name')
            ->withPivot('value', 'expires_at');
    }

    /**
     * Get the users associated with this feature flag.
     *
     * @return BelongsToMany<User, Model>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'feature_flag_user', 'feature_name', 'user_id', 'feature_name')
            ->withPivot('value', 'expires_at');
    }

    /**
     * Check if the feature flag is within its active date range
     */
    public function isWithinDateRange(): bool
    {
        $now = now();

        if ($this->starts_at !== null && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Get the cache key for this feature flag
     */
    public function getCacheKey(): string
    {
        return "feature_flag:{$this->feature_name}";
    }

    /**
     * Get the Pennant feature name
     */
    public function getPennantName(): string
    {
        return $this->feature_name;
    }

    /**
     * Verifica se um feature flag é compatível com o ambiente atual
     */
    public function isCompatibleWithEnvironment(): bool
    {
        if ($this->type !== FeatureFlagType::ENVIRONMENT) {
            return true;
        }

        $currentEnv   = app()->environment();
        $environments = $this->parameters['environments'] ?? [];

        return is_array($environments) && in_array($currentEnv, $environments, true);
    }

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'default_value' => 'boolean',
            'parameters'    => 'array',
            'starts_at'     => 'datetime',
            'ends_at'       => 'datetime',
            'type'          => FeatureFlagType::class,
        ];
    }
}
