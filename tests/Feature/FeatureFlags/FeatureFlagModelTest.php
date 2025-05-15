<?php declare(strict_types = 1);

use App\Enums\FeatureFlagType;
use App\Models\{FeatureFlag, Tenant, User};
use App\Providers\FeatureFlagServiceProvider;
use Laravel\Pennant\Feature;

// Setup similar to FeatureFlagTestCase
beforeEach(function (): void {
    config(['pennant.default' => 'array']);
    app()->register(FeatureFlagServiceProvider::class);
    Feature::purge();
    Feature::resolveScopeUsing(static function (?string $driver = null) {
        return null;
    });
});

test('feature flag id is string', function () {
    $feature = FeatureFlag::factory()->create();

    expect(is_string($feature->id) || is_numeric($feature->id))->toBeTrue('ID should be a string or numeric value, got: ' . gettype($feature->id));
});
test('feature flag can be created with default factory', function () {
    $feature = FeatureFlag::factory()->create();

    $this->assertDatabaseHas('feature_flag_metadata', [
        'feature_name' => $feature->feature_name,
        'display_name' => $feature->display_name,
    ]);

    expect($feature->type)->toBeInstanceOf(FeatureFlagType::class);
});
test('feature flag has relationships with tenants and users', function () {
    $feature = FeatureFlag::factory()->perTenant()->create();
    $tenant  = Tenant::factory()->create();
    $user    = User::factory()->create();

    $feature->tenants()->attach($tenant, ['value' => true]);
    $feature->users()->attach($user, ['value' => false]);

    expect($feature->tenants)->toHaveCount(1)
        ->and($feature->users)->toHaveCount(1)
        ->and($feature->tenants->first()->id)->toEqual($tenant->id)
        ->and($feature->users->first()->id)->toEqual($user->id)
        ->and((int)$feature->tenants->first()->pivot->value)->toBe(1)
        ->and((int)$feature->users->first()->pivot->value)->toBe(0);

});
test('is within date range method', function () {
    $featureNoDate = FeatureFlag::factory()->create([
        'starts_at' => null,
        'ends_at'   => null,
    ]);
    expect($featureNoDate->isWithinDateRange())->toBeTrue();

    $featureFutureStart = FeatureFlag::factory()->create([
        'starts_at' => now()->addDays(5),
        'ends_at'   => null,
    ]);
    expect($featureFutureStart->isWithinDateRange())->toBeFalse();

    $featurePastEnd = FeatureFlag::factory()->create([
        'starts_at' => null,
        'ends_at'   => now()->subDays(5),
    ]);
    expect($featurePastEnd->isWithinDateRange())->toBeFalse();

    $featureActive = FeatureFlag::factory()->create([
        'starts_at' => now()->subDays(5),
        'ends_at'   => now()->addDays(5),
    ]);
    expect($featureActive->isWithinDateRange())->toBeTrue();
});
test('is compatible with environment method', function () {
    $currentEnv = 'testing';
    app()->detectEnvironment(fn () => $currentEnv);

    $featureNonEnv = FeatureFlag::factory()->global()->create();
    expect($featureNonEnv->isCompatibleWithEnvironment())->toBeTrue();

    $featureMatching = FeatureFlag::factory()->environment([$currentEnv])->create();
    expect($featureMatching->isCompatibleWithEnvironment())->toBeTrue();

    $featureNonMatching = FeatureFlag::factory()->environment(['production'])->create();
    expect($featureNonMatching->isCompatibleWithEnvironment())->toBeFalse();

    $featureMultiple = FeatureFlag::factory()->environment(['production', $currentEnv, 'staging'])->create();
    expect($featureMultiple->isCompatibleWithEnvironment())->toBeTrue();
});
test('get pennant name method', function () {
    $featureName = 'custom_feature_name';
    $feature     = FeatureFlag::factory()->create(['feature_name' => $featureName]);

    expect($feature->getPennantName())->toEqual($featureName);
});
