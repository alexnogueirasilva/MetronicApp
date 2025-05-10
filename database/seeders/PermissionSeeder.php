<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Auth\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
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

    }
}
