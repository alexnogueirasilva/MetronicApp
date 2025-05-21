<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\{Tenant, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            'id'         => Str::lower(Str::ulid()),
            'tenant_id'  => $tenant->id,
            'nickname'   => 'alex',
            'first_name' => 'Alex',
            'last_name'  => 'Nogueira',
            'email'      => 'alex@devaction.com.br',
            'password'   => 'password',
        ]);

        $this->call([
            RolePermissionSeeder::class,
        ]);
    }
}
