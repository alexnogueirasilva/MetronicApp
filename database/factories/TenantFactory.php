<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Enums\PlanType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'          => $this->faker->company(),
            'domain'        => $this->faker->unique()->domainName(),
            'plan'          => PlanType::BASIC,
            'is_active'     => true,
            'trial_ends_at' => $this->faker->optional(0.3)->dateTimeBetween('+1 week', '+1 month'),
            'settings'      => [],
        ];
    }

    /**
     * Indicate that the tenant is on the free plan.
     */
    public function free(): self
    {
        return $this->state(fn (array $attributes): array => [
            'plan' => PlanType::FREE,
        ]);
    }

    /**
     * Indicate that the tenant is on the professional plan.
     */
    public function professional(): self
    {
        return $this->state(fn (array $attributes): array => [
            'plan' => PlanType::PROFESSIONAL,
        ]);
    }

    /**
     * Indicate that the tenant is on the enterprise plan.
     */
    public function enterprise(): self
    {
        return $this->state(fn (array $attributes): array => [
            'plan' => PlanType::ENTERPRISE,
        ]);
    }

    /**
     * Indicate that the tenant is on the unlimited plan.
     */
    public function unlimited(): self
    {
        return $this->state(fn (array $attributes): array => [
            'plan' => PlanType::UNLIMITED,
        ]);
    }

    /**
     * Indicate that the tenant is inactive.
     */
    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the tenant has a custom rate limit.
     */
    public function withCustomRateLimit(int $rateLimit): self
    {
        return $this->state(fn (array $attributes): array => [
            'settings' => array_merge($attributes['settings'] ?? [], [
                'custom_rate_limit' => $rateLimit,
            ]),
        ]);
    }
}
