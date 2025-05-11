<?php declare(strict_types = 1);

namespace Database\Factories\Auth;

use App\Models\Auth\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email'      => $this->faker->unique()->safeEmail(),
            'code'       => $this->faker->randomNumber(6, true),
            'type'       => $this->faker->randomElement(['email', 'totp']),
            'expires_at' => now()->addMinutes(5),
            'used'       => false,
        ];
    }
}
