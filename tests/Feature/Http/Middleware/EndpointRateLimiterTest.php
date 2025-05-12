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
    // Criar tenant e usuário
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);

    // Testar o limite para login (0.2x)
    $loginResponse = actingAs($user)->getJson('/api/auth/login');
    $loginResponse->assertOk();

    // Testar o limite para forgot-password (0.1x)
    $forgotResponse = actingAs($user)->getJson('/api/auth/forgot-password');
    $forgotResponse->assertOk();

    // Testar o limite para reports (0.5x)
    $reportsResponse = actingAs($user)->getJson('/api/reports/daily');
    $reportsResponse->assertOk();

    // Testar o limite para endpoint normal (1.0x)
    $normalResponse = actingAs($user)->getJson('/api/normal-endpoint');
    $normalResponse->assertOk();

    // Verificar que os limites são diferentes para cada endpoint
    $loginLimit   = (int) $loginResponse->headers->get('X-RateLimit-Limit');
    $forgotLimit  = (int) $forgotResponse->headers->get('X-RateLimit-Limit');
    $reportsLimit = (int) $reportsResponse->headers->get('X-RateLimit-Limit');
    $normalLimit  = (int) $normalResponse->headers->get('X-RateLimit-Limit');

    // Login deve ter um limite menor que o normal
    expect($loginLimit)->toBeLessThan($normalLimit);

    // Forgot deve ter o menor limite
    expect($forgotLimit)->toBeLessThan($loginLimit);

    // Reports deve ter um limite menor que o normal, mas maior que login
    expect($reportsLimit)->toBeLessThan($normalLimit);
    expect($reportsLimit)->toBeGreaterThan($loginLimit);
});

it('enforces limits for specific endpoints correctly', function () {
    // Criar tenant com configurações que facilitem o teste
    $tenant = Tenant::factory()->create([
        'settings' => ['custom_rate_limit' => 5], // Limite base de 5 req/min
    ]);

    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    // Para /api/auth/forgot-password, o multiplicador é 0.1, então o limite será apenas 1 req/min
    $response = actingAs($user)->getJson('/api/auth/forgot-password');
    $response->assertOk();

    // Verificar que o limite é 1 (5 * 0.1 arredondado para 1)
    expect($response->headers->get('X-RateLimit-Limit'))->toBe('1');
    expect($response->headers->get('X-RateLimit-Remaining'))->toBe('0');
});

it('handles pattern matching for endpoints correctly', function () {
    // Criar tenant e usuário
    $tenant = Tenant::factory()->create();
    $user   = User::factory()->create(['tenant_id' => $tenant->id]);

    // Testar padrão de wildcards para endpoints OTP
    $response = actingAs($user)->getJson('/api/auth/otp/request');
    $response->assertOk();

    // O padrão 'api/auth/otp/*' deve aplicar o multiplicador 0.2
    $baseLimit     = $user->getRateLimitPerMinute();
    $expectedLimit = max(1, (int) ceil($baseLimit * 0.2));

    // Verificar que o cabeçalho de limite está presente
    expect($response->headers->has('X-RateLimit-Limit'))->toBeTrue();

    // Esperar que o limite seja menor que o limite base
    $actualLimit = (int) $response->headers->get('X-RateLimit-Limit');
    expect($actualLimit)->toBeLessThan($baseLimit);
});
