<?php declare(strict_types = 1);

use App\Models\{Tenant, User};
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\{actingAs};

beforeEach(function () {
    // Registrar rotas de teste com diferentes padrões de endpoint
    Route::middleware(['api', 'auth:sanctum', 'endpoint.ratelimit'])
        ->group(function () {
            Route::get('/api/auth/login', fn () => response()->json(['message' => 'login']));
            Route::get('/api/auth/forgot-password', fn () => response()->json(['message' => 'forgot']));
            Route::get('/api/auth/otp/request', fn () => response()->json(['message' => 'otp']));
            Route::get('/api/reports/daily', fn () => response()->json(['message' => 'reports']));
            Route::get('/api/normal-endpoint', fn () => response()->json(['message' => 'normal']));
        });
});

it('applies different rate limits to different endpoints', function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);

    $loginResponse = actingAs($user)->getJson('/api/auth/login');
    $loginResponse->assertOk();

    $forgotResponse = actingAs($user)->getJson('/api/auth/forgot-password');
    $forgotResponse->assertOk();

    $reportsResponse = actingAs($user)->getJson('/api/reports/daily');
    $reportsResponse->assertOk();

    $normalResponse = actingAs($user)->getJson('/api/normal-endpoint');
    $normalResponse->assertOk();

    $loginLimit   = (int) $loginResponse->headers->get('X-RateLimit-Limit');
    $forgotLimit  = (int) $forgotResponse->headers->get('X-RateLimit-Limit');
    $reportsLimit = (int) $reportsResponse->headers->get('X-RateLimit-Limit');
    $normalLimit  = (int) $normalResponse->headers->get('X-RateLimit-Limit');

    expect($loginLimit)->toBeLessThan($normalLimit)
        ->and($forgotLimit)->toBeLessThan($loginLimit)
        ->and($reportsLimit)->toBeLessThan($normalLimit)
        ->and($reportsLimit)->toBeGreaterThan($loginLimit);
});

it('enforces limits for specific endpoints correctly', function () {
    $tenant = Tenant::factory()->create([
        'settings' => ['custom_rate_limit' => 5],
    ]);

    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = actingAs($user)->getJson('/api/auth/forgot-password');
    $response->assertOk();

    expect($response->headers->get('X-RateLimit-Limit'))->toBe('1')
        ->and($response->headers->get('X-RateLimit-Remaining'))->toBe('0');
});

it('handles pattern matching for endpoints correctly', function () {
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = actingAs($user)->getJson('/api/auth/otp/request');
    $response->assertOk();

    $baseLimit     = $user->getRateLimitPerMinute();
    $expectedLimit = max(1, (int) ceil($baseLimit * 0.2));

    expect($response->headers->has('X-RateLimit-Limit'))->toBeTrue();

    $actualLimit = (int) $response->headers->get('X-RateLimit-Limit');
    expect($actualLimit)->toBeLessThan($baseLimit);
});
