<?php
declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Concerns\HasUlids, Model};
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\{Carbon, Str};
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property string $id
 * @property string $impersonator_id
 * @property string $impersonated_id
 * @property ?Carbon $ended_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $impersonator
 * @property-read User $impersonated
 */
class Impersonation extends Model implements Auditable
{
    use AuditableTrait;
    use HasUlids;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ended_at' => 'datetime',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (Impersonation $model): void {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::ulid();
            }
        });
    }

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
    public function scopeActive(Builder $query): Builder
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
