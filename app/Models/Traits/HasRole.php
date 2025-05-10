<?php declare(strict_types = 1);

namespace App\Models\Traits;

use App\Models\Auth\{Permission, Role};
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, Pivot};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use UnexpectedValueException;

trait HasRole
{
    /**
     * Atribui uma única role ao usuário, removendo anteriores.
     */
    public function assignRole(string|Role $role): void
    {
        $roleInstance = $role instanceof Role
            ? $role
            : Role::query()->where('name', $role)->firstOrFail();

        // Garante que só haja uma role por vez
        $this->roles()
            ->wherePivot('role_id', '!=', $roleInstance->id)
            ->detach();

        $this->roles()->syncWithoutDetaching([$roleInstance->id]);

        $this->clearRoleCache();
    }

    /**
     * Relacionamento many-to-many com Role.
     *
     * @return BelongsToMany<Role, Role, Pivot>
     */
    public function roles(): BelongsToMany
    {
        /** @var BelongsToMany<Role, Role, Pivot> */
        return $this->belongsToMany(
            related: Role::class,
            table: 'role_user',
            foreignPivotKey: 'user_id',
            relatedPivotKey: 'role_id'
        );
    }

    /**
     * Limpa o cache de roles e permissões.
     */
    public function clearRoleCache(): void
    {
        Cache::forget("user_{$this->id}_roles");
        $this->clearPermissionCache();
    }

    /**
     * Limpa o cache de permissões do usuário.
     */
    public function clearPermissionCache(): void
    {
        Cache::forget("user_{$this->id}_permissions");
    }

    /**
     * Verifica se o usuário possui uma role específica.
     */
    public function hasRole(string|Role $role): bool
    {
        $roleName = $role instanceof Role ? $role->name : $role;

        return $this->getRole()?->name === $roleName;
    }

    /**
     * Retorna a role atual do usuário (apenas uma).
     */
    public function getRole(): ?Role
    {
        return $this->roles()->with('permissions')->first();
    }

    /**
     * Verifica se o usuário possui uma permissão via role.
     */
    public function hasPermission(string|Permission $permission): bool
    {
        $permissionName = $permission instanceof Permission ? $permission->name : $permission;

        return $this->getCachedPermissions()->contains('name', $permissionName);
    }

    /**
     * Retorna permissões da role com cache.
     *
     * @return Collection<int, Permission>
     */
    public function getCachedPermissions(): Collection
    {
        $permissions = Cache::remember(
            "user_{$this->id}_permissions",
            now()->addMinutes(60),
            fn (): Collection => $this->roles()->with('permissions')->get()->flatMap(
                fn (Role $role): Collection => $role->permissions
            )
        );

        if (!$permissions instanceof Collection) {
            throw new UnexpectedValueException('Cached permissions must be a Collection instance.');
        }

        return $permissions;
    }
}
