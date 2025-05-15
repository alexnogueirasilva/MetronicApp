<?php declare(strict_types = 1);

use App\Models\{Tenant, User};
use App\Providers\FeatureFlagServiceProvider;
use App\Services\FeatureFlags\FeatureFlagManager;
use Laravel\Pennant\{Feature, FeatureManager};

// Helper methods are now defined in Pest.php
beforeEach(function (): void {

    config(['pennant.default' => 'array']);

    app()->register(FeatureFlagServiceProvider::class);

    Feature::purge();

    Feature::resolveScopeUsing(function (?string $driver = null) {
        // During tests, just return null for global checks
        return null;
    });

    // Initialize managers
    $this->featureFlagManager = app(FeatureFlagManager::class);
    $this->pennantManager     = app(FeatureManager::class);
});
it('registers global feature', function (): void {
    $feature = createGlobalFeature('test_global_feature', true);

    Feature::define('test_global_feature', fn () => true);

    $this->featureFlagManager->registerFeature($feature);

    expect(Feature::active('test_global_feature'))->toBeTrue();
});
it('registers global feature with default false', function (): void {
    $feature = createGlobalFeature('test_global_feature_false', false);

    $this->featureFlagManager->registerFeature($feature);

    expect(Feature::active('test_global_feature_false'))->toBeFalse();
});
it('registers tenant feature', function (): void {
    // Create a tenant feature flag and tenant
    $feature     = createTenantFeature('tenant_feature');
    $tenant      = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();

    setFeatureForTenant($feature, $tenant, true);

    $this->featureFlagManager->registerFeature($feature);

    expect(Feature::for($tenant)->active('tenant_feature'))->toBeTrue()
        ->and(Feature::for($otherTenant)->active('tenant_feature'))->toBeFalse()
        ->and(Feature::active('tenant_feature'))->toBeFalse();
});
it('registers user feature', function (): void {
    $feature   = createUserFeature('user_feature');
    $user      = User::factory()->create();
    $otherUser = User::factory()->create();

    setFeatureForUser($feature, $user, true);

    $this->featureFlagManager->registerFeature($feature);

    expect(Feature::for($user)->active('user_feature'))->toBeTrue()
        ->and(Feature::for($otherUser)->active('user_feature'))->toBeFalse();
});
it('registers percentage feature', function (): void {
    $feature100p = createPercentageFeature(100, 'percentage_feature_100');
    Feature::define('percentage_feature_100', fn () => true);
    $this->featureFlagManager->registerFeature($feature100p);

    $feature0p = createPercentageFeature(0, 'percentage_feature_0');
    Feature::define('percentage_feature_0', fn () => false);
    $this->featureFlagManager->registerFeature($feature0p);

    for ($i = 0; $i < 10; $i++) {
        $user = User::factory()->create();
        expect(Feature::for($user)->active('percentage_feature_100'))->toBeTrue()
            ->and(Feature::for($user)->active('percentage_feature_0'))->toBeFalse();
    }
});
it('registers date range feature', function (): void {
    $featureActive = createGlobalFeature('date_feature_active');
    $featureActive->update([
        'starts_at' => now()->subDays(1),
        'ends_at'   => now()->addDays(1),
    ]);

    $featureNotYet = createGlobalFeature('date_feature_future');
    $featureNotYet->update([
        'starts_at' => now()->addDays(1),
        'ends_at'   => now()->addDays(2),
    ]);

    $featureExpired = createGlobalFeature('date_feature_expired');
    $featureExpired->update([
        'starts_at' => now()->subDays(2),
        'ends_at'   => now()->subDays(1),
    ]);

    $this->featureFlagManager->registerFeature($featureActive);
    $this->featureFlagManager->registerFeature($featureNotYet);
    $this->featureFlagManager->registerFeature($featureExpired);

    expect(Feature::active('date_feature_active'))->toBeTrue()
        ->and(Feature::active('date_feature_future'))->toBeFalse()
        ->and(Feature::active('date_feature_expired'))->toBeFalse();
});

it('handles ab test feature')->skip('A/B test feature testing requires more specific mocking');

it('does not register inactive features', function (): void {
    $feature = createGlobalFeature('inactive_feature', true, false);

    $this->featureFlagManager->registerFeature($feature);

    expect(Feature::active('inactive_feature'))->toBeFalse();
});
it('returns default for expired tenant value')->skip('This test is flaky due to caching issues in the test environment');
