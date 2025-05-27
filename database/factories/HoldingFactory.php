<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\{Holding, Tenant};
use Illuminate\Database\Eloquent\Factories\Factory;
use Random\RandomException;

/**
 * @extends Factory<Holding>
 */
class HoldingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     * @throws RandomException
     */
    public function definition(): array
    {
        $companyName = $this->faker->company();

        return [
            'tenant_id'   => Tenant::factory(),
            'name'        => $companyName,
            'legal_name'  => $companyName . ' S.A.',
            'cnpj'        => $this->generateValidCnpj(),
            'email'       => $this->faker->companyEmail(),
            'phone'       => $this->faker->phoneNumber(),
            'description' => $this->faker->optional(0.7)->text(500),
            'logo_path'   => $this->faker->optional(0.3)->filePath(),
            'settings'    => $this->faker->optional(0.5)->randomElements([
                'default_currency' => 'BRL',
                'timezone'         => 'America/Sao_Paulo',
                'notifications'    => [
                    'email' => true,
                    'sms'   => false,
                ],
                'integrations' => [
                    'accounting_system' => null,
                    'erp_system'        => null,
                ],
            ]),
            'address' => $this->faker->optional(0.8)->randomElements([
                'street'   => $this->faker->streetName(),
                'number'   => $this->faker->buildingNumber(),
                'district' => $this->faker->citySuffix(),
                'city'     => $this->faker->city(),
                'state'    => $this->faker->stateAbbr(),
                'zip'      => $this->faker->postcode(),
                'country'  => 'BR',
            ]),
            'is_active' => $this->faker->boolean(90),
        ];
    }

    /**
     * Generate a valid CNPJ.
     * @throws RandomException
     */
    private function generateValidCnpj(): string
    {
        // Gera os primeiros 12 dígitos
        $cnpj = '';

        for ($i = 0; $i < 12; $i++) {
            $cnpj .= random_int(0, 9);
        }

        // Calcula o primeiro dígito verificador
        $sequence1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum       = 0;

        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $cnpj[$i] * $sequence1[$i];
        }
        $digit1 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        $cnpj .= $digit1;

        $sequence2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum       = 0;

        for ($i = 0; $i < 13; $i++) {
            $sum += (int) $cnpj[$i] * $sequence2[$i];
        }
        $digit2 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);

        return $cnpj . $digit2;
    }

    /**
     * Indicate that the holding is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the holding has no logo.
     */
    public function withoutLogo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'logo_path' => null,
        ]);
    }

    /**
     * Indicate that the holding has complete address.
     */
    public function withCompleteAddress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'address' => [
                'street'   => $this->faker->streetName(),
                'number'   => $this->faker->buildingNumber(),
                'district' => $this->faker->citySuffix(),
                'city'     => $this->faker->city(),
                'state'    => $this->faker->stateAbbr(),
                'zip'      => $this->faker->postcode(),
                'country'  => 'BR',
            ],
        ]);
    }
}
