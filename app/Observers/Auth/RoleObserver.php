<?php declare(strict_types = 1);

namespace App\Observers\Auth;

use App\Models\Auth\Role;
use Illuminate\Support\Facades\Cache;

class RoleObserver
{
    public function updating(Role $role): void
    {
        self::clearPermissionsCacheForAllUsers($role);
    }

    protected static function clearPermissionsCacheForAllUsers(Role $role): void
    {
        foreach ($role->users()->get() as $user) {
            Cache::forget("user_{$user->id}_permissions");
        }
    }

    public function deleting(Role $role): void
    {
        self::clearPermissionsCacheForAllUsers($role);
    }

    public function deleted(Role $role): void
    {
        // Este método pode ser necessário para limpar qualquer cache adicional após a exclusão completa
    }
}
