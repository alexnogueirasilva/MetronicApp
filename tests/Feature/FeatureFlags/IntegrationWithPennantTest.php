<?php declare(strict_types = 1);

use App\Models\{Tenant, User};
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

// Helper methods are now defined in Pest.php

test('pennant native api works with our system', function () {
    $feature = createGlobalFeature('pennant_native_test', true);

    Feature::activate('pennant_native_test', true);

    expect(Feature::active('pennant_native_test'))->toBeTrue();

    Feature::deactivate('pennant_native_test');

    expect(Feature::active('pennant_native_test'))->toBeFalse();
});
test('scoped features work with pennant api', function () {
    $user      = User::factory()->create();
    $tenant    = Tenant::factory()->create();
    $otherUser = User::factory()->create(['tenant_id' => $tenant->id]);

    Feature::for($user)->activate('user_specific_feature');

    expect(Feature::for($user)->active('user_specific_feature'))->toBeTrue()
        ->and(Feature::for($otherUser)->active('user_specific_feature'))->toBeFalse();

    Feature::for($tenant)->activate('tenant_specific_feature');
    expect(Feature::for($tenant)->active('tenant_specific_feature'))->toBeTrue();

    Feature::activate('tenant_specific_feature');
    expect(Feature::active('tenant_specific_feature'))->toBeTrue();
});
test('value features work with our system', function () {
    $feature = createABTestFeature([
        'red'   => 0.25,
        'green' => 0.25,
        'blue'  => 0.5,
    ], 'blue', 'color_test');

    Feature::define('color_test', function ($scope) {
        return 'blue';
    });

    $users = User::factory()->count(10)->create();

    foreach ($users as $user) {
        $value = Feature::for($user)->value('color_test');

        expect(['red', 'green', 'blue', 'purple'])->toContain($value);

        Feature::for($user)->activate('color_test', 'purple');
        expect(Feature::for($user)->value('color_test'))->toEqual('purple');
    }
});
test('conditional feature execution works', function () {
    $feature = createGlobalFeature('conditional_feature', false);

    $defaultExecuted = false;
    $featureExecuted = false;

    Feature::when(
        'conditional_feature',
        function () use (&$featureExecuted) {
            $featureExecuted = true;

            return 'feature enabled';
        },
        function () use (&$defaultExecuted) {
            $defaultExecuted = true;

            return 'feature disabled';
        }
    );

    expect($featureExecuted)->toBeFalse()
        ->and($defaultExecuted)->toBeTrue();

    Feature::activate('conditional_feature');

    $defaultExecuted = false;
    $featureExecuted = false;

    Feature::when(
        'conditional_feature',
        function () use (&$featureExecuted) {
            $featureExecuted = true;

            return 'feature enabled';
        },
        function () use (&$defaultExecuted) {
            $defaultExecuted = true;

            return 'feature disabled';
        }
    );

    expect($featureExecuted)->toBeTrue()
        ->and($defaultExecuted)->toBeFalse();
});
