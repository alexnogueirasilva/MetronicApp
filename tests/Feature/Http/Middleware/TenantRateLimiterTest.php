<?php declare(strict_types = 1);

namespace Tests\Feature\Http\Middleware;

use App\Enums\PlanType;
use App\Models\{Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantRateLimiterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Registrar rota de teste com o middleware de rate limiting
        Route::middleware(['api', 'auth:sanctum', 'tenant.ratelimit'])
            ->get('/api/test-route', fn () => response()->json(['message' => 'success']));
    }

    public function test_rate_limiting_based_on_tenant_plan(): void
    {
        // Criar tenants com diferentes planos
        $freeTenant         = Tenant::factory()->create(['plan' => PlanType::FREE]);
        $basicTenant        = Tenant::factory()->create(['plan' => PlanType::BASIC]);
        $professionalTenant = Tenant::factory()->create(['plan' => PlanType::PROFESSIONAL]);

        // Criar usuários para cada tenant
        $freeUser         = User::factory()->create(['tenant_id' => $freeTenant->id]);
        $basicUser        = User::factory()->create(['tenant_id' => $basicTenant->id]);
        $professionalUser = User::factory()->create(['tenant_id' => $professionalTenant->id]);

        // Testar limite para plano FREE (30 req/min)
        $response = $this->actingAs($freeUser)->getJson('/api/test-route');
        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '30');

        // Testar limite para plano BASIC (60 req/min)
        $response = $this->actingAs($basicUser)->getJson('/api/test-route');
        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '60');

        // Testar limite para plano PROFESSIONAL (300 req/min)
        $response = $this->actingAs($professionalUser)->getJson('/api/test-route');
        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '300');
    }

    public function test_unlimited_plan_has_no_rate_limit(): void
    {
        // Criar tenant com plano ilimitado
        $unlimitedTenant = Tenant::factory()->create(['plan' => PlanType::UNLIMITED]);
        $unlimitedUser   = User::factory()->create(['tenant_id' => $unlimitedTenant->id]);

        // Testar ausência de limite para plano UNLIMITED
        $response = $this->actingAs($unlimitedUser)->getJson('/api/test-route');
        $response->assertOk();
        $response->assertHeaderMissing('X-RateLimit-Limit');
    }

    public function test_custom_rate_limit_overrides_plan_limit(): void
    {
        // Criar tenant com limite personalizado
        $tenant = Tenant::factory()->create([
            'plan'     => PlanType::BASIC,
            'settings' => ['custom_rate_limit' => 100],
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // Testar que o limite personalizado é usado em vez do padrão do plano
        $response = $this->actingAs($user)->getJson('/api/test-route');
        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '100');
    }

    public function test_rate_limit_header_shows_correct_remaining_count(): void
    {
        // Para simplificar o teste, criamos um tenant com um limite muito baixo
        $tenant = Tenant::factory()->create([
            'settings' => ['custom_rate_limit' => 1], // Apenas 1 requisição permitida por minuto
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        // Requisição deve mostrar o limite correto
        $response = $this->actingAs($user)->getJson('/api/test-route');
        $response->assertOk();
        $response->assertHeader('X-RateLimit-Limit', '1');
        $response->assertHeader('X-RateLimit-Remaining', '0');
    }
}
