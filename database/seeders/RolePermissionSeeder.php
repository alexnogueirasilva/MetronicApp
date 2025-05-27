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
                Permission::query()->firstOrCreate([
                    'name' => "{$subject}:{$action}",
                ]);
            }
        }

        $roles = [
            [
                'name'         => 'Administrator',
                'icon'         => 'Settings',
                'icon_class'   => 'text-xl text-blue-400',
                'fill_class'   => 'fill-blue-50 dark:fill-blue-950/30',
                'stroke_class' => 'stroke-blue-200 dark:stroke-blue-950',
                'size_class'   => 'size-[44px]',
                'description'  => 'Manages system settings and user access, ensures system stability.',
                'is_default'   => true,
            ],
            [
                'name'         => 'Viewer',
                'icon'         => 'Eye',
                'icon_class'   => 'text-xl text-orange-400',
                'fill_class'   => 'fill-orange-50 dark:fill-orange-950/30',
                'stroke_class' => 'stroke-orange-200 dark:stroke-orange-950',
                'size_class'   => 'size-[44px]',
                'description'  => "Can view data but doesn't have editing privileges.",
                'is_default'   => true,
            ],
            [
                'name'         => 'Remote Developer',
                'icon'         => 'Fingerprint',
                'icon_class'   => 'text-xl text-green-400',
                'fill_class'   => 'fill-green-50 dark:fill-green-950/30',
                'stroke_class' => 'stroke-green-200 dark:stroke-green-950',
                'size_class'   => 'size-[44px]',
                'description'  => 'Develops features remotely and supports backend systems.',
                'is_default'   => false,
            ],
            [
                'name'         => 'Customer Support',
                'icon'         => 'Truck',
                'icon_class'   => 'text-xl text-red-400',
                'fill_class'   => 'fill-red-50 dark:fill-red-950/30',
                'stroke_class' => 'stroke-red-200 dark:stroke-red-950',
                'size_class'   => 'size-[44px]',
                'description'  => 'Provides assistance and resolves customer inquiries and issues.',
                'is_default'   => true,
            ],
            [
                'name'         => 'Project Manager',
                'icon'         => 'LineChart',
                'icon_class'   => 'text-xl text-violet-400',
                'fill_class'   => 'fill-violet-50 dark:fill-violet-950/30',
                'stroke_class' => 'stroke-violet-200 dark:stroke-violet-950',
                'size_class'   => 'size-[44px]',
                'description'  => "Oversees projects, ensures they're on time and within budget.",
                'is_default'   => true,
            ],
            [
                'name'         => 'Remote Designer',
                'icon'         => 'PenTool',
                'icon_class'   => 'text-xl text-muted-foreground',
                'fill_class'   => 'fill-muted/30',
                'stroke_class' => 'stroke-input',
                'size_class'   => 'size-[44px]',
                'description'  => 'Creates visual designs remotely for various projects.',
                'is_default'   => false,
            ],
            [
                'name'         => 'HR Manager',
                'icon'         => 'Users',
                'icon_class'   => 'text-xl text-green-400',
                'fill_class'   => 'fill-green-50 dark:fill-green-950/30',
                'stroke_class' => 'stroke-green-200 dark:stroke-green-950',
                'size_class'   => 'size-[44px]',
                'description'  => 'Manages human resources, recruitment, and employee relations.',
                'is_default'   => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::query()->firstOrCreate(
                ['name' => $roleData['name']],
                collect($roleData)->except('name')->toArray()
            );
        }

        $user = User::query()->where('email', 'alex@devaction.com.br')->first();

        $adminRole = Role::query()->where('name', 'Administrator')->first();
        $user->role()->sync($adminRole);

        $adminRole->permissions()->sync(Permission::pluck('id'));
    }
}
