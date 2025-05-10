<?php
declare(strict_types = 1);

namespace App\Models\Auth;

use App\Models\User;
use Database\Factories\Auth\RoleFactory;
use DevactionLabs\FilterablePackage\Traits\Filterable;
use Illuminate\Database\Eloquent\{Collection, Model};
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, HasMany};
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property ?string $icon
 * @property ?string $description
 * @property bool $is_default
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Permission[]|Collection $permissions
 */
class Role extends Model
{
    use Filterable;
    /** @use HasFactory<RoleFactory> */
    use HasFactory;
    use HasUlids;

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return array{granted: \Illuminate\Support\Collection<int, Permission>, revoked: \Illuminate\Support\Collection<int, Permission>}
     */
    public function groupedPermissions(): array
    {
        $grantedIds = $this->permissions->pluck('id');

        $all = Permission::query()->get(['id', 'name']);

        return [
            'granted' => $all->whereIn('id', $grantedIds)->values(),
            'revoked' => $all->whereNotIn('id', $grantedIds)->values(),
        ];
    }

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
