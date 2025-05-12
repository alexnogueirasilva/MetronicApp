<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Enums\PlanType;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a default tenant for development
        Tenant::factory()->create([
            'name'      => 'DevAction',
            'domain'    => 'devaction.com.br',
            'plan'      => PlanType::UNLIMITED,
            'is_active' => true,
        ]);

        // Create sample tenants with different plans
        Tenant::factory()->free()->create([
            'name' => 'Free Tenant',
        ]);

        Tenant::factory()->professional()->create([
            'name' => 'Professional Tenant',
        ]);

        Tenant::factory()->enterprise()->create([
            'name' => 'Enterprise Tenant',
        ]);

        // Create a tenant with custom rate limit
        Tenant::factory()->withCustomRateLimit(500)->create([
            'name' => 'Custom Rate Limit Tenant',
        ]);
    }
}
