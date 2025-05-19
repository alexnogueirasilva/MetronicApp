<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\{Tenant, User};
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'name'   => 'DevAction',
            'domain' => 'devaction.com.br',
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'name'      => 'Alex Nogueira',
            'email'     => 'alex@devaction.com.br',
            'password'  => 'password',
        ]);

        $this->call([
            RolePermissionSeeder::class,
        ]);
    }
}
