<?php
declare(strict_types = 1);

namespace App\Models\Auth;

use App\Models\User;
use App\Observers\Auth\RoleObserver;
use Database\Factories\Auth\RoleFactory;
use DevactionLabs\FilterablePackage\Traits\Filterable;
use Illuminate\Database\Eloquent\{Attributes\ObservedBy, Collection, Model};
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany};
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

#[ObservedBy(RoleObserver::class)]
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
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /**
     * @return array{
     *     granted: Collection<int, Permission>,
     *     revoked: Collection<int, Permission>
     * }
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
