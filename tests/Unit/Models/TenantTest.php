<?php declare(strict_types = 1);
use App\Enums\PlanType;
use App\Models\{Tenant, User};

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('tenant has users relationship', function () {
    // Criar tenant e usuários associados
    $tenant = Tenant::factory()->create();
    $users  = User::factory()->count(3)->create(['tenant_id' => $tenant->id]);

    // Verificar que a relação está funcionando
    expect($tenant->users)->toHaveCount(3);
    expect($tenant->users->first())->toBeInstanceOf(User::class);
});
test('on trial returns correct value', function () {
    // Tenant sem data de trial
    $noTrialTenant = Tenant::factory()->create(['trial_ends_at' => null]);
    expect($noTrialTenant->onTrial())->toBeFalse();

    // Tenant com trial expirado
    $expiredTrialTenant = Tenant::factory()->create([
        'trial_ends_at' => now()->subDays(1),
    ]);
    expect($expiredTrialTenant->onTrial())->toBeFalse();

    // Tenant com trial ativo
    $activeTrialTenant = Tenant::factory()->create([
        'trial_ends_at' => now()->addDays(10),
    ]);
    expect($activeTrialTenant->onTrial())->toBeTrue();
});
test('get rate limit per minute', function () {
    // Tenant com plano FREE sem limite personalizado
    $freeTenant = Tenant::factory()->create(['plan' => PlanType::FREE]);
    expect($freeTenant->getRateLimitPerMinute())->toEqual(30);

    // Tenant com plano PROFESSIONAL sem limite personalizado
    $proTenant = Tenant::factory()->create(['plan' => PlanType::PROFESSIONAL]);
    expect($proTenant->getRateLimitPerMinute())->toEqual(300);

    // Tenant com limite personalizado
    $customTenant = Tenant::factory()->create([
        'plan'     => PlanType::BASIC,
        'settings' => ['custom_rate_limit' => 100],
    ]);
    expect($customTenant->getRateLimitPerMinute())->toEqual(100);
});
test('get max concurrent requests', function () {
    // Tenant com plano FREE sem limite personalizado
    $freeTenant = Tenant::factory()->create(['plan' => PlanType::FREE]);
    expect($freeTenant->getMaxConcurrentRequests())->toEqual(5);

    // Tenant com plano ENTERPRISE sem limite personalizado
    $enterpriseTenant = Tenant::factory()->create(['plan' => PlanType::ENTERPRISE]);
    expect($enterpriseTenant->getMaxConcurrentRequests())->toEqual(50);

    // Tenant com limite personalizado
    $customTenant = Tenant::factory()->create([
        'plan'     => PlanType::BASIC,
        'settings' => ['max_concurrent_requests' => 25],
    ]);
    expect($customTenant->getMaxConcurrentRequests())->toEqual(25);
});
test('get rate limit cache key', function () {
    $tenant = Tenant::factory()->create(['id' => 123]);
    expect($tenant->getRateLimitCacheKey())->toEqual('tenant:123:ratelimit');
});
test('tenant casts attributes correctly', function () {
    $tenant = Tenant::factory()->create([
        'is_active'     => 1,
        'plan'          => PlanType::BASIC,
        'settings'      => ['foo' => 'bar'],
        'trial_ends_at' => '2023-12-31 23:59:59',
    ]);

    expect($tenant->is_active)->toBeBool();
    expect($tenant->is_active)->toBeTrue();

    expect($tenant->plan)->toBeInstanceOf(PlanType::class);
    expect($tenant->plan)->toEqual(PlanType::BASIC);

    expect($tenant->settings)->toBeArray();
    expect($tenant->settings['foo'])->toEqual('bar');

    expect($tenant->trial_ends_at)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});
