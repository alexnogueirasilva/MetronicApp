<?php declare(strict_types = 1);

use App\Models\{FeatureFlag, Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
  ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

function createUserWithTenant(): User
{
    $tenant = Tenant::factory()->create();

    return User::factory()->create(['tenant_id' => $tenant->id]);
}

function createGlobalFeature(
    string $featureName = null,
    bool $defaultValue = true,
    bool $isActive = true
): FeatureFlag {
    return FeatureFlag::factory()->global($defaultValue)->create([
        'feature_name' => $featureName ?? 'feature_' . uniqid('', true),
        'is_active'    => $isActive,
    ]);
}

function createTenantFeature(
    string $featureName = null,
    bool $defaultValue = false,
    bool $isActive = true
): FeatureFlag {
    return FeatureFlag::factory()->perTenant($defaultValue)->create([
        'feature_name' => $featureName ?? 'tenant_feature_' . uniqid('', true),
        'is_active'    => $isActive,
    ]);
}

function createUserFeature(
    string $featureName = null,
    bool $defaultValue = false,
    bool $isActive = true
): FeatureFlag {
    return FeatureFlag::factory()->perUser($defaultValue)->create([
        'feature_name' => $featureName ?? 'user_feature_' . uniqid('', true),
        'is_active'    => $isActive,
    ]);
}

function createPercentageFeature(
    int $percentage = 50,
    string $featureName = null,
    bool $defaultValue = false,
    bool $isActive = true
): FeatureFlag {
    return FeatureFlag::factory()->percentage($percentage)->create([
        'feature_name'  => $featureName ?? 'percentage_feature_' . uniqid('', true),
        'default_value' => $defaultValue,
        'is_active'     => $isActive,
    ]);
}

function createABTestFeature(
    ?array $variants = null,
    ?string $defaultVariant = null,
    ?string $featureName = null,
    bool $isActive = true
): FeatureFlag {
    return FeatureFlag::factory()->abTest($variants, $defaultVariant)->create([
        'feature_name' => $featureName ?? 'ab_test_feature_' . uniqid('', true),
        'is_active'    => $isActive,
    ]);
}

function setFeatureForTenant(
    FeatureFlag $feature,
    Tenant $tenant,
    bool $value = true,
    ?DateTimeInterface $expiresAt = null
): void {
    $feature->tenants()->attach($tenant, [
        'value'      => $value,
        'expires_at' => $expiresAt,
    ]);

    // Set the flag in Pennant too
    Feature::for($tenant)->activate($feature->feature_name, $value);
}

function setFeatureForUser(
    FeatureFlag $feature,
    User $user,
    bool $value = true,
    ?DateTimeInterface $expiresAt = null
): void {
    $feature->users()->attach($user, [
        'value'      => $value,
        'expires_at' => $expiresAt,
    ]);

    // Set the flag in Pennant too
    Feature::for($user)->activate($feature->feature_name, $value);
}

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}
