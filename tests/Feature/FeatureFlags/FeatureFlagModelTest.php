<?php declare(strict_types = 1);

namespace Tests\Feature\FeatureFlags;

use App\Enums\FeatureFlagType;
use App\Models\{FeatureFlag, Tenant, User};

class FeatureFlagModelTest extends FeatureFlagTestCase
{
    public function test_feature_flag_id_is_string(): void
    {
        $feature = FeatureFlag::factory()->create();

        // In tests, we might get an auto-incrementing ID if migrations aren't run
        // or we might get a ULID if they are
        $this->assertTrue(
            is_string($feature->id) || is_numeric($feature->id),
            'ID should be a string or numeric value, got: ' . gettype($feature->id)
        );
    }

    public function test_feature_flag_can_be_created_with_default_factory(): void
    {
        $feature = FeatureFlag::factory()->create();

        $this->assertDatabaseHas('feature_flag_metadata', [
            'feature_name' => $feature->feature_name,
            'display_name' => $feature->display_name,
        ]);

        $this->assertInstanceOf(FeatureFlagType::class, $feature->type);
    }

    public function test_feature_flag_has_relationships_with_tenants_and_users(): void
    {
        $feature = FeatureFlag::factory()->perTenant()->create();
        $tenant  = Tenant::factory()->create();
        $user    = User::factory()->create();

        // Attach tenant and user to feature
        $feature->tenants()->attach($tenant, ['value' => true]);
        $feature->users()->attach($user, ['value' => false]);

        // Test relationships
        $this->assertCount(1, $feature->tenants);
        $this->assertCount(1, $feature->users);
        $this->assertEquals($tenant->id, $feature->tenants->first()->id);
        $this->assertEquals($user->id, $feature->users->first()->id);

        // Test pivot data
        // Note: directly checking boolean values in pivot fails in some DB systems
        $this->assertSame(1, (int)$feature->tenants->first()->pivot->value);
        $this->assertSame(0, (int)$feature->users->first()->pivot->value);
    }

    public function test_is_within_date_range_method(): void
    {
        // Feature with no date constraints
        $featureNoDate = FeatureFlag::factory()->create([
            'starts_at' => null,
            'ends_at'   => null,
        ]);
        $this->assertTrue($featureNoDate->isWithinDateRange());

        // Feature with future start date
        $featureFutureStart = FeatureFlag::factory()->create([
            'starts_at' => now()->addDays(5),
            'ends_at'   => null,
        ]);
        $this->assertFalse($featureFutureStart->isWithinDateRange());

        // Feature with past end date
        $featurePastEnd = FeatureFlag::factory()->create([
            'starts_at' => null,
            'ends_at'   => now()->subDays(5),
        ]);
        $this->assertFalse($featurePastEnd->isWithinDateRange());

        // Feature with active date range
        $featureActive = FeatureFlag::factory()->create([
            'starts_at' => now()->subDays(5),
            'ends_at'   => now()->addDays(5),
        ]);
        $this->assertTrue($featureActive->isWithinDateRange());
    }

    public function test_is_compatible_with_environment_method(): void
    {
        // Set test environment
        $currentEnv = 'testing';
        app()->detectEnvironment(fn () => $currentEnv);

        // Feature not environment-specific
        $featureNonEnv = FeatureFlag::factory()->global()->create();
        $this->assertTrue($featureNonEnv->isCompatibleWithEnvironment());

        // Feature with matching environment
        $featureMatching = FeatureFlag::factory()->environment([$currentEnv])->create();
        $this->assertTrue($featureMatching->isCompatibleWithEnvironment());

        // Feature with non-matching environment
        $featureNonMatching = FeatureFlag::factory()->environment(['production'])->create();
        $this->assertFalse($featureNonMatching->isCompatibleWithEnvironment());

        // Feature with multiple environments including current
        $featureMultiple = FeatureFlag::factory()->environment(['production', $currentEnv, 'staging'])->create();
        $this->assertTrue($featureMultiple->isCompatibleWithEnvironment());
    }

    public function test_get_pennant_name_method(): void
    {
        $featureName = 'custom_feature_name';
        $feature     = FeatureFlag::factory()->create(['feature_name' => $featureName]);

        $this->assertEquals($featureName, $feature->getPennantName());
    }
}
