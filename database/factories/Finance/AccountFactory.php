<?php declare(strict_types = 1);

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use Database\Factories\{TenantFactory, UserFactory};
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id'   => TenantFactory::new(),
            'name'        => $this->faker->company,
            'description' => $this->faker->sentence,
            'type'        => $this->faker->randomElement(['savings', 'checking', 'credit']),
            'currency'    => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'balance'     => $this->faker->randomFloat(2, 0, 10000),
            'created_by'  => UserFactory::new(),
        ];
    }
}
