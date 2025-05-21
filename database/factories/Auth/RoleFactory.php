<?php declare(strict_types = 1);

namespace Database\Factories\Auth;

use App\Models\Auth\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'         => $this->faker->unique()->word(),
            'icon'         => $this->faker->word(),
            'icon_class'   => $this->faker->word(),
            'fill_class'   => $this->faker->word(),
            'stroke_class' => $this->faker->word(),
            'size_class'   => $this->faker->word(),
            'description'  => $this->faker->sentence(),
            'is_default'   => false,
        ];
    }
}
