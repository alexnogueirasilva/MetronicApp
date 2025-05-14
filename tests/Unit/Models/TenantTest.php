<?php declare(strict_types = 1);

namespace Tests\Unit\Models;

use App\Enums\PlanType;
use App\Models\{Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_has_users_relationship(): void
    {
        // Criar tenant e usuários associados
        $tenant = Tenant::factory()->create();
        $users  = User::factory()->count(3)->create(['tenant_id' => $tenant->id]);

        // Verificar que a relação está funcionando
        $this->assertCount(3, $tenant->users);
        $this->assertInstanceOf(User::class, $tenant->users->first());
    }

    public function test_on_trial_returns_correct_value(): void
    {
        // Tenant sem data de trial
        $noTrialTenant = Tenant::factory()->create(['trial_ends_at' => null]);
        $this->assertFalse($noTrialTenant->onTrial());

        // Tenant com trial expirado
        $expiredTrialTenant = Tenant::factory()->create([
            'trial_ends_at' => now()->subDays(1),
        ]);
        $this->assertFalse($expiredTrialTenant->onTrial());

        // Tenant com trial ativo
        $activeTrialTenant = Tenant::factory()->create([
            'trial_ends_at' => now()->addDays(10),
        ]);
        $this->assertTrue($activeTrialTenant->onTrial());
    }

    public function test_get_rate_limit_per_minute(): void
    {
        // Tenant com plano FREE sem limite personalizado
        $freeTenant = Tenant::factory()->create(['plan' => PlanType::FREE]);
        $this->assertEquals(30, $freeTenant->getRateLimitPerMinute());

        // Tenant com plano PROFESSIONAL sem limite personalizado
        $proTenant = Tenant::factory()->create(['plan' => PlanType::PROFESSIONAL]);
        $this->assertEquals(300, $proTenant->getRateLimitPerMinute());

        // Tenant com limite personalizado
        $customTenant = Tenant::factory()->create([
            'plan'     => PlanType::BASIC,
            'settings' => ['custom_rate_limit' => 100],
        ]);
        $this->assertEquals(100, $customTenant->getRateLimitPerMinute());
    }

    public function test_get_max_concurrent_requests(): void
    {
        // Tenant com plano FREE sem limite personalizado
        $freeTenant = Tenant::factory()->create(['plan' => PlanType::FREE]);
        $this->assertEquals(5, $freeTenant->getMaxConcurrentRequests());

        // Tenant com plano ENTERPRISE sem limite personalizado
        $enterpriseTenant = Tenant::factory()->create(['plan' => PlanType::ENTERPRISE]);
        $this->assertEquals(50, $enterpriseTenant->getMaxConcurrentRequests());

        // Tenant com limite personalizado
        $customTenant = Tenant::factory()->create([
            'plan'     => PlanType::BASIC,
            'settings' => ['max_concurrent_requests' => 25],
        ]);
        $this->assertEquals(25, $customTenant->getMaxConcurrentRequests());
    }

    public function test_get_rate_limit_cache_key(): void
    {
        $tenant = Tenant::factory()->create(['id' => 123]);
        $this->assertEquals('tenant:123:ratelimit', $tenant->getRateLimitCacheKey());
    }

    public function test_tenant_casts_attributes_correctly(): void
    {
        $tenant = Tenant::factory()->create([
            'is_active'     => 1,
            'plan'          => PlanType::BASIC,
            'settings'      => ['foo' => 'bar'],
            'trial_ends_at' => '2023-12-31 23:59:59',
        ]);

        $this->assertIsBool($tenant->is_active);
        $this->assertTrue($tenant->is_active);

        $this->assertInstanceOf(PlanType::class, $tenant->plan);
        $this->assertEquals(PlanType::BASIC, $tenant->plan);

        $this->assertIsArray($tenant->settings);
        $this->assertEquals('bar', $tenant->settings['foo']);

        $this->assertInstanceOf(\Carbon\CarbonImmutable::class, $tenant->trial_ends_at);
    }
}
