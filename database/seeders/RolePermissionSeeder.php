<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Auth\{Permission, Role};
use App\Models\User;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            'workspace-settings',
            'billing-management',
            'integration-setup',
            'map-creation',
            'data-export',
            'user-roles',
            'security-settings',
            'insights-access',
            'merchant-list',
        ];

        $actions = ['view', 'modify', 'publish', 'configure'];

        foreach ($subjects as $subject) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$subject}:{$action}",
                ]);
            }
        }

        $roles = [
            'Administrator',
            'Viewer',
            'Remote Developer',
            'Customer Support',
            'Project Manager',
            'Remote Designer',
            'HR Manager',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
            ]);
        }

        $user = User::query()->where('email', 'alex@devaction.com.br')->first();

        $adminRole = Role::where('name', 'Administrator')->first();
        $user->role()->sync($adminRole);

        $adminRole->permissions()->sync(Permission::pluck('id'));
    }
}
