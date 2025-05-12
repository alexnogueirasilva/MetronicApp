<?php declare(strict_types = 1);

namespace App\Models;

use App\Enums\TypeOtp;
use App\Models\Auth\{Role};
use App\Models\Traits\HasRole;
use Database\Factories\UserFactory;
use DevactionLabs\FilterablePackage\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\{Carbon};
use Laravel\Sanctum\HasApiTokens;

/**
 * @property string $id
 * @property ?int $tenant_id
 * @property string $name
 * @property string $email
 * @property string $avatar
 * @property string $password
 * @property ?string $remember_token
 * @property ?Carbon $email_verified_at
 * @property ?string $totp_secret
 * @property ?string $otp_method
 * @property bool $totp_verified
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ?Tenant $tenant
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use HasRole;
    use Filterable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function role(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Get the tenant that the user belongs to.
     *
     * @return BelongsTo<Tenant, User>
     */
    public function tenant(): BelongsTo
    {
        /** @var BelongsTo<Tenant, User> */
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the rate limit for this user
     */
    public function getRateLimitPerMinute(): int
    {
        // Check if user has a custom rate limit in their settings
        // This would be implemented in a settings feature

        // If no custom limit, delegate to the tenant's rate limit
        return $this->tenant?->getRateLimitPerMinute() ?? 30; // Default to 30 if no tenant
    }

    /**
     * Get the rate limit cache key for this user
     */
    public function getRateLimitCacheKey(): string
    {
        return "user:{$this->id}:ratelimit";
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'otp_method'        => TypeOtp::class,
            'totp_verified'     => 'bool',
        ];
    }

}
