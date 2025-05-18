<?php
declare(strict_types = 1);

namespace App\Actions\FeatureFlags;

use App\Enums\FeatureFlagType;
use App\Models\FeatureFlag;
use App\Services\FeatureFlags\FeatureFlagManager;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

readonly class CreateFeatureFlagAction
{
    public function __construct(
        private FeatureFlagManager $featureFlagManager,
    ) {}

    /**
     * Cria um novo feature flag global
     */
    public function createGlobal(
        string $key,
        string $name,
        ?string $description = null,
        bool $defaultValue = false,
        bool $isActive = true
    ): FeatureFlag {
        return $this->create(
            key: $key,
            name: $name,
            type: FeatureFlagType::GLOBAL,
            description: $description,
            defaultValue: $defaultValue,
            isActive: $isActive,
        );
    }

    /**
     * @param  array<string, mixed>|null  $parameters
     */
    private function create(
        string $key,
        string $name,
        FeatureFlagType $type,
        ?string $description = null,
        bool $defaultValue = false,
        bool $isActive = true,
        ?array $parameters = null,
        ?Carbon $startsAt = null,
        ?Carbon $endsAt = null,
    ): FeatureFlag {
        $existingFeature = FeatureFlag::query()->where('key', $key)->first();

        if ($existingFeature) {
            throw new InvalidArgumentException("Feature flag with key '{$key}' already exists.");
        }

        /** @var FeatureFlag $feature */
        $feature = FeatureFlag::query()->create([
            'key'           => $key,
            'name'          => $name,
            'description'   => $description,
            'type'          => $type,
            'parameters'    => $parameters,
            'default_value' => $defaultValue,
            'is_active'     => $isActive,
            'starts_at'     => $startsAt,
            'ends_at'       => $endsAt,
        ]);

        if ($isActive) {
            $this->featureFlagManager->registerFeature($feature);
        }

        return $feature;
    }

    /**
     * Cria um novo feature flag por tenant
     */
    public function createPerTenant(
        string $key,
        string $name,
        ?string $description = null,
        bool $defaultValue = false,
        bool $isActive = true
    ): FeatureFlag {
        return $this->create(
            key: $key,
            name: $name,
            type: FeatureFlagType::PER_TENANT,
            description: $description,
            defaultValue: $defaultValue,
            isActive: $isActive,
        );
    }

    /**
     * Cria um novo feature flag por usuário
     */
    public function createPerUser(
        string $key,
        string $name,
        ?string $description = null,
        bool $defaultValue = false,
        bool $isActive = true
    ): FeatureFlag {
        return $this->create(
            key: $key,
            name: $name,
            type: FeatureFlagType::PER_USER,
            description: $description,
            defaultValue: $defaultValue,
            isActive: $isActive,
        );
    }

    /**
     * Cria um novo feature flag baseado em porcentagem
     */
    public function createPercentage(
        string $key,
        string $name,
        int $percentage,
        ?string $description = null,
        bool $defaultValue = false,
        bool $isActive = true
    ): FeatureFlag {
        $parameters = ['percentage' => min(max($percentage, 0), 100)];

        return $this->create(
            key: $key,
            name: $name,
            type: FeatureFlagType::PERCENTAGE,
            description: $description,
            defaultValue: $defaultValue,
            isActive: $isActive,
            parameters: $parameters,
        );
    }

    /**
     * Cria um novo feature flag por ambiente
     */

    /**
     * Cria um novo feature flag por período
     */
    public function createDateRange(
        string $key,
        string $name,
        ?Carbon $startsAt = null,
        ?Carbon $endsAt = null,
        ?string $description = null,
        bool $defaultValue = false,
        bool $isActive = true
    ): FeatureFlag {
        return $this->create(
            key: $key,
            name: $name,
            type: FeatureFlagType::DATE_RANGE,
            description: $description,
            defaultValue: $defaultValue,
            isActive: $isActive,
            startsAt: $startsAt,
            endsAt: $endsAt,
        );
    }

    /**
     * Cria um novo feature flag para teste A/B
     */

    /**
     * @param  array<string>  $environments  List of environment names
     */
    public function createEnvironment(
        string $key,
        string $name,
        array $environments,
        ?string $description = null,
        bool $defaultValue = false,
        bool $isActive = true
    ): FeatureFlag {
        $parameters = ['environments' => $environments];

        return $this->create(
            key: $key,
            name: $name,
            type: FeatureFlagType::ENVIRONMENT,
            description: $description,
            defaultValue: $defaultValue,
            isActive: $isActive,
            parameters: $parameters,
        );
    }

    /**
     * Método genérico para criar um feature flag
     */

    /**
     * @param  array<string, float>  $variants  Map of variant names to weights
     */
    public function createABTest(
        string $key,
        string $name,
        array $variants,
        ?string $defaultVariant = null,
        ?string $description = null,
        bool $isActive = true
    ): FeatureFlag {
        $parameters = [
            'variants'        => $variants,
            'default_variant' => $defaultVariant,
        ];

        return $this->create(
            key: $key,
            name: $name,
            type: FeatureFlagType::AB_TEST,
            description: $description,
            defaultValue: false,
            isActive: $isActive,
            parameters: $parameters,
        );
    }
}
