<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Enums\FeatureFlagType;
use App\Models\FeatureFlag;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * @extends Factory<FeatureFlag>
 */
class FeatureFlagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FeatureFlag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(FeatureFlagType::cases());

        return [
            'id'            => (string) Str::ulid(),
            'feature_name'  => 'feature_' . Str::lower(Str::random(8)),
            'display_name'  => $this->faker->words(3, true),
            'description'   => $this->faker->optional(0.8)->sentence(),
            'type'          => $type,
            'parameters'    => $this->getParametersForType($type),
            'default_value' => $this->faker->boolean(80),
            'is_active'     => $this->faker->boolean(90),
            'starts_at'     => $this->faker->optional(0.3)->dateTimeBetween('-1 month', '+1 month'),
            'ends_at'       => $this->faker->optional(0.3)->dateTimeBetween('+1 month', '+6 months'),
        ];
    }

    /**
     * Configure the model factory to create a global feature flag.
     */
    public function global(bool $defaultValue = false): self
    {
        return $this->state(fn (array $attributes): array => [
            'type'          => FeatureFlagType::GLOBAL,
            'default_value' => $defaultValue,
            'parameters'    => null,
        ]);
    }

    /**
     * Configure the model factory to create a per-tenant feature flag.
     */
    public function perTenant(bool $defaultValue = false): self
    {
        return $this->state(fn (array $attributes): array => [
            'type'          => FeatureFlagType::PER_TENANT,
            'default_value' => $defaultValue,
            'parameters'    => null,
        ]);
    }

    /**
     * Configure the model factory to create a per-user feature flag.
     */
    public function perUser(bool $defaultValue = false): self
    {
        return $this->state(fn (array $attributes): array => [
            'type'          => FeatureFlagType::PER_USER,
            'default_value' => $defaultValue,
            'parameters'    => null,
        ]);
    }

    /**
     * Configure the model factory to create a percentage-based feature flag.
     */
    public function percentage(int $percentage = 50): self
    {
        return $this->state(fn (array $attributes): array => [
            'type'       => FeatureFlagType::PERCENTAGE,
            'parameters' => ['percentage' => $percentage],
        ]);
    }

    /**
     * Configure the model factory to create a date range feature flag.
     */
    public function dateRange(DateTimeInterface $startDate, DateTimeInterface $endDate): self
    {
        return $this->state(fn (array $attributes): array => [
            'type'      => FeatureFlagType::DATE_RANGE,
            'starts_at' => $startDate,
            'ends_at'   => $endDate,
        ]);
    }

    /**
     * Configure the model factory to create an environment-specific feature flag.
     */
    public function environment(?array $environments = null): self
    {
        $environments ??= [Config::get('app.env')];

        return $this->state(fn (array $attributes): array => [
            'type'       => FeatureFlagType::ENVIRONMENT,
            'parameters' => ['environments' => $environments],
        ]);
    }

    /**
     * Configure the model factory to create an A/B test feature flag.
     */
    public function abTest(?array $variants = null, ?string $defaultVariant = null): self
    {
        $variants ??= [
            'A' => 0.5,
            'B' => 0.5,
        ];

        return $this->state(fn (array $attributes): array => [
            'type'       => FeatureFlagType::AB_TEST,
            'parameters' => [
                'variants'        => $variants,
                'default_variant' => $defaultVariant ?? array_key_first($variants),
            ],
        ]);
    }

    /**
     * Get the parameters based on the feature flag type.
     */
    protected function getParametersForType(FeatureFlagType $type): ?array
    {
        return match($type) {
            FeatureFlagType::PERCENTAGE => [
                'percentage' => $this->faker->numberBetween(1, 100),
            ],
            FeatureFlagType::ENVIRONMENT => [
                'environments' => [$this->faker->randomElement(['local', 'testing', 'staging', 'production'])],
            ],
            FeatureFlagType::AB_TEST => [
                'variants'        => ['A' => 0.5, 'B' => 0.5],
                'default_variant' => 'A',
            ],
            default => null,
        };
    }
}
