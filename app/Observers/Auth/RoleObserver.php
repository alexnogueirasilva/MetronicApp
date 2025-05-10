<?php declare(strict_types = 1);

namespace App\Observers\Auth;

use App\Models\Auth\Role;
use Illuminate\Support\Facades\Cache;

class RoleObserver
{
    public function updated(Role $role): void
    {
        self::clearPermissionsCacheForAllUsers($role);
    }

    protected static function clearPermissionsCacheForAllUsers(Role $role): void
    {
        foreach ($role->users as $user) {
            Cache::forget("user_{$user->id}_permissions");
        }
    }

    public function deleted(Role $role): void
    {
        self::clearPermissionsCacheForAllUsers($role);
    }

}
