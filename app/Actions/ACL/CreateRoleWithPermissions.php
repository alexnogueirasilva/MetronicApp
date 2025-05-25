<?php declare(strict_types = 1);

namespace App\Actions\ACL;

use App\DTO\ACL\CreateRoleData;
use App\Models\Auth\Role;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateRoleWithPermissions
{
    /**
     * @throws Throwable
     */
    public function execute(CreateRoleData $data): Role
    {
        return DB::transaction(static function () use ($data): Role {
            $role = new Role([
                'name'        => $data->name,
                'description' => $data->description,
                'icon'        => $data->icon,
            ]);

            $role->save();

            $role->permissions()->sync($data->permissions ?? []);

            return $role;
        });
    }
}
