<?php declare(strict_types = 1);

namespace Tests\Feature\FeatureFlags;

use App\Models\{FeatureFlag, Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

abstract class FeatureFlagTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Pennant is using an in-memory store for testing
        config(['pennant.default' => 'array']);

        // Register service provider manually for tests
        app()->register(\App\Providers\FeatureFlagServiceProvider::class);

        // Clear any existing features between tests
        \Laravel\Pennant\Feature::purge();

        // Set up the global scope resolver to ensure it works without auth in tests
        \Laravel\Pennant\Feature::resolveScopeUsing(function (?string $driver = null) {
            // During tests, just return null for global checks
            return null;
        });
    }

    /**
     * Create a test user with associated tenant
     */
    protected function createUserWithTenant(): User
    {
        $tenant = Tenant::factory()->create();

        return User::factory()->create(['tenant_id' => $tenant->id]);
    }

    /**
     * Create global feature flag
     */
    protected function createGlobalFeature(
        string $featureName = null,
        bool $defaultValue = true,
        bool $isActive = true
    ): FeatureFlag {
        return FeatureFlag::factory()->global($defaultValue)->create([
            'feature_name' => $featureName ?? 'feature_' . uniqid(),
            'is_active'    => $isActive,
        ]);
    }

    /**
     * Create tenant-specific feature flag
     */
    protected function createTenantFeature(
        string $featureName = null,
        bool $defaultValue = false,
        bool $isActive = true
    ): FeatureFlag {
        return FeatureFlag::factory()->perTenant($defaultValue)->create([
            'feature_name' => $featureName ?? 'tenant_feature_' . uniqid(),
            'is_active'    => $isActive,
        ]);
    }

    /**
     * Create user-specific feature flag
     */
    protected function createUserFeature(
        string $featureName = null,
        bool $defaultValue = false,
        bool $isActive = true
    ): FeatureFlag {
        return FeatureFlag::factory()->perUser($defaultValue)->create([
            'feature_name' => $featureName ?? 'user_feature_' . uniqid(),
            'is_active'    => $isActive,
        ]);
    }

    /**
     * Create percentage-based feature flag
     */
    protected function createPercentageFeature(
        int $percentage = 50,
        string $featureName = null,
        bool $defaultValue = false,
        bool $isActive = true
    ): FeatureFlag {
        return FeatureFlag::factory()->percentage($percentage)->create([
            'feature_name'  => $featureName ?? 'percentage_feature_' . uniqid(),
            'default_value' => $defaultValue,
            'is_active'     => $isActive,
        ]);
    }

    /**
     * Create A/B test feature flag
     */
    protected function createABTestFeature(
        array $variants = null,
        string $defaultVariant = null,
        string $featureName = null,
        bool $isActive = true
    ): FeatureFlag {
        return FeatureFlag::factory()->abTest($variants, $defaultVariant)->create([
            'feature_name' => $featureName ?? 'ab_test_feature_' . uniqid(),
            'is_active'    => $isActive,
        ]);
    }

    /**
     * Set feature flag value for tenant
     */
    protected function setFeatureForTenant(
        FeatureFlag $feature,
        Tenant $tenant,
        bool $value = true,
        \DateTimeInterface $expiresAt = null
    ): void {
        $feature->tenants()->attach($tenant, [
            'value'      => $value,
            'expires_at' => $expiresAt,
        ]);

        // Set the flag in Pennant too
        Feature::for($tenant)->activate($feature->feature_name, $value);
    }

    /**
     * Set feature flag value for user
     */
    protected function setFeatureForUser(
        FeatureFlag $feature,
        User $user,
        bool $value = true,
        \DateTimeInterface $expiresAt = null
    ): void {
        $feature->users()->attach($user, [
            'value'      => $value,
            'expires_at' => $expiresAt,
        ]);

        // Set the flag in Pennant too
        Feature::for($user)->activate($feature->feature_name, $value);
    }
}
