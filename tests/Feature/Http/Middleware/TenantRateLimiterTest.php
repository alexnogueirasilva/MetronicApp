<?php declare(strict_types = 1);

use App\Enums\PlanType;
use App\Models\{Tenant, User};
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\{actingAs};

beforeEach(function () {
    Route::middleware(['api', 'auth:sanctum', 'tenant.ratelimit'])
        ->get('/api/test-route', fn () => response()->json(['message' => 'success']));
});

it('applies rate limiting based on tenant plan', function () {
    $freeTenant         = Tenant::factory()->create(['plan' => PlanType::FREE]);
    $basicTenant        = Tenant::factory()->create(['plan' => PlanType::BASIC]);
    $professionalTenant = Tenant::factory()->create(['plan' => PlanType::PROFESSIONAL]);

    $freeUser         = User::factory()->create(['tenant_id' => $freeTenant->id]);
    $basicUser        = User::factory()->create(['tenant_id' => $basicTenant->id]);
    $professionalUser = User::factory()->create(['tenant_id' => $professionalTenant->id]);

    $response = actingAs($freeUser)->getJson('/api/test-route');
    $response->assertOk();
    $response->assertHeader('X-RateLimit-Limit', '30');

    $response = actingAs($basicUser)->getJson('/api/test-route');
    $response->assertOk();
    $response->assertHeader('X-RateLimit-Limit', '60');

    $response = actingAs($professionalUser)->getJson('/api/test-route');
    $response->assertOk();
    $response->assertHeader('X-RateLimit-Limit', '300');
});

it('does not apply rate limits to unlimited plans', function () {
    $unlimitedTenant = Tenant::factory()->create(['plan' => PlanType::UNLIMITED]);
    $unlimitedUser   = User::factory()->create(['tenant_id' => $unlimitedTenant->id]);

    $response = actingAs($unlimitedUser)->getJson('/api/test-route');
    $response->assertOk();
    $response->assertHeaderMissing('X-RateLimit-Limit');
});

it('uses custom rate limit instead of plan default', function () {
    $tenant = Tenant::factory()->create([
        'plan'     => PlanType::BASIC,
        'settings' => ['custom_rate_limit' => 100],
    ]);

    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = actingAs($user)->getJson('/api/test-route');
    $response->assertOk();
    $response->assertHeader('X-RateLimit-Limit', '100');
});

it('shows correct remaining requests count in headers', function () {
    $tenant = Tenant::factory()->create([
        'settings' => ['custom_rate_limit' => 1],
    ]);

    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = actingAs($user)->getJson('/api/test-route');
    $response->assertOk();
    $response->assertHeader('X-RateLimit-Limit', '1');
    $response->assertHeader('X-RateLimit-Remaining', '0');
});
