<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property int $id
 * @property int $impersonator_id
 * @property int $impersonated_id
 * @property ?Carbon $ended_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $impersonator
 * @property-read User $impersonated
 */
class Impersonation extends Model implements Auditable
{
    use AuditableTrait;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ended_at' => 'datetime',
    ];

    /**
     * Get the impersonator user.
     *
     * @return BelongsTo<User, Impersonation>
     */
    public function impersonator(): BelongsTo
    {
        /** @var BelongsTo<User, Impersonation> */
        return $this->belongsTo(User::class, 'impersonator_id');
    }

    /**
     * Get the impersonated user.
     *
     * @return BelongsTo<User, Impersonation>
     */
    public function impersonated(): BelongsTo
    {
        /** @var BelongsTo<User, Impersonation> */
        return $this->belongsTo(User::class, 'impersonated_id');
    }

    /**
     * Check if the impersonation is active.
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Scope a query to only include active impersonations.
     *
     * @param Builder<Impersonation> $query
     * @return Builder<Impersonation>
     */
    public function scopeActive($query)
    {
        return $query->whereNull('ended_at');
    }

    /**
     * End the impersonation session.
     */
    public function end(): void
    {
        // @phpstan-ignore-next-line
        $this->ended_at = now();
        $this->save();
    }
}
