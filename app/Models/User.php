<?php
declare(strict_types = 1);

namespace App\Models;

use App\Enums\TypeOtp;
use App\Models\Auth\{Role};
use App\Models\Traits\HasRole;
use Database\Factories\UserFactory;
use DevactionLabs\FilterablePackage\Traits\Filterable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\{Carbon};
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property string $id
 * @property ?int $tenant_id
 * @property string $nickname
 * @property ?string $first_name
 * @property ?string $last_name
 * @property string $email
 * @property string $avatar
 * @property ?string $password
 * @property ?string $remember_token
 * @property ?Carbon $email_verified_at
 * @property ?string $provider
 * @property ?string $provider_id
 * @property ?string $totp_secret
 * @property ?string $otp_method
 * @property bool $totp_verified
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read ?Tenant $tenant
 * @property-read FeatureFlag[] $featureFlags
 * @property-read Impersonation[] $impersonations
 * @property-read Impersonation[] $beingImpersonated
 */
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements Auditable
{
    use AuditableTrait;
    use Filterable;
    use HasApiTokens;
    use HasFactory;
    use HasRole;
    use HasUlids;
    use Notifiable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'provider',
        'provider_id',
    ];

    /**
     * Find or create a user based on OAuth data
     *
     * @param  array<string, string|null>  $userData
     */
    public static function findOrCreateSocialUser(string $provider, string $providerId, array $userData): self
    {
        $user = self::query()->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($user) {
            return $user;
        }

        $user = self::query()->where('email', $userData['email'])->first();

        if ($user) {
            $user->update([
                'provider'    => $provider,
                'provider_id' => $providerId,
                'avatar'      => $userData['avatar'] ?? $user->avatar,
            ]);

            return $user;
        }

        return self::query()->create([
            'nickname'          => $userData['nickname'],
            'email'             => $userData['email'],
            'avatar'            => $userData['avatar'] ?? null,
            'provider'          => $provider,
            'provider_id'       => $providerId,
            'email_verified_at' => now(),
        ]);
    }

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
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the feature flags associated with this user.
     *
     * @return BelongsToMany<FeatureFlag, User>
     */
    public function featureFlags(): BelongsToMany
    {
        return $this->belongsToMany(FeatureFlag::class)->withPivot('value', 'expires_at');
    }

    /**
     * Get the rate limit for this user
     */
    public function getRateLimitPerMinute(): int
    {
        return $this->tenant?->getRateLimitPerMinute() ?? 30;
    }

    /**
     * Get the rate limit cache key for this user
     */
    public function getRateLimitCacheKey(): string
    {
        return "user:{$this->id}:ratelimit";
    }

    /**
     * Check if this user is currently impersonating another user.
     */
    public function isImpersonating(): bool
    {
        return $this->impersonations()->active()->exists();
    }

    /**
     * Get impersonations started by this user.
     *
     * @return HasMany<Impersonation, User>
     */
    public function impersonations(): HasMany
    {
        /** @var HasMany<Impersonation, User> */
        return $this->hasMany(Impersonation::class, 'impersonator_id');
    }

    /**
     * Get the current active impersonation.
     */
    public function activeImpersonation(): ?Impersonation
    {
        return $this->impersonations()->active()->first();
    }

    /**
     * Check if this user is currently being impersonated.
     */
    public function isBeingImpersonated(): bool
    {
        return $this->beingImpersonated()->active()->exists();
    }

    /**
     * Get impersonations where this user is being impersonated.
     *
     * @return HasMany<Impersonation, User>
     */
    public function beingImpersonated(): HasMany
    {
        /** @var HasMany<Impersonation, User> */
        return $this->hasMany(Impersonation::class, 'impersonated_id');
    }

    /**
     * Get the current active impersonation where this user is being impersonated.
     */
    public function activeBeingImpersonated(): ?Impersonation
    {
        return $this->beingImpersonated()->active()->first();
    }

    /**
     * Check if this user is an admin (based on role).
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * The attributes that should be cast.
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
