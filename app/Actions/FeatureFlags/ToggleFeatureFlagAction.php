<?php
declare(strict_types = 1);

namespace App\Actions\FeatureFlags;

use App\Models\{FeatureFlag, Tenant, User};
use App\Services\FeatureFlags\FeatureFlagManager;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Laravel\Pennant\Feature;

readonly class ToggleFeatureFlagAction
{
    public function __construct(
        private FeatureFlagManager $featureFlagManager,
    ) {}

    /**
     * Ativa ou desativa um feature flag para todos (global)
     */
    public function toggleGlobal(string $key, bool $value): void
    {
        $feature = $this->getFeatureByKey($key);

        $feature->update([
            'default_value' => $value,
        ]);

        Feature::activate($key, $value);
        $this->featureFlagManager->clearFeatureCache($key);
    }

    /**
     * Obtém um feature flag pelo seu key, lançando exceção se não existir
     */
    private function getFeatureByKey(string $key): FeatureFlag
    {
        /** @var FeatureFlag|null $feature */
        $feature = FeatureFlag::where('key', $key)->first();

        if (!$feature) {
            throw new InvalidArgumentException("Feature flag with key '{$key}' not found.");
        }

        return $feature;
    }

    /**
     * Ativa ou desativa um feature flag para um tenant específico
     */
    public function toggleForTenant(string $key, Tenant $tenant, bool $value, ?Carbon $expiresAt = null): void
    {
        $feature = $this->getFeatureByKey($key);

        $tenant->featureFlags()->syncWithoutDetaching([
            $feature->id => [
                'value'      => $value,
                'expires_at' => $expiresAt,
            ],
        ]);

        Feature::for($tenant)->activate($key, $value);
        $this->featureFlagManager->clearFeatureCache($key);
    }

    /**
     * Ativa ou desativa um feature flag para um usuário específico
     */
    public function toggleForUser(string $key, User $user, bool $value, ?Carbon $expiresAt = null): void
    {
        $feature = $this->getFeatureByKey($key);

        $user->featureFlags()->syncWithoutDetaching([
            $feature->id => [
                'value'      => $value,
                'expires_at' => $expiresAt,
            ],
        ]);

        Feature::for($user)->activate($key, $value);
        $this->featureFlagManager->clearFeatureCache($key);
    }

    /**
     * Atualiza o percentual de rollout para um feature flag
     */
    public function updatePercentage(string $key, int $percentage): void
    {
        $feature = $this->getFeatureByKey($key);

        $parameters               = $feature->parameters ?? [];
        $parameters['percentage'] = min(max($percentage, 0), 100);

        $feature->update([
            'parameters' => $parameters,
        ]);

        $this->featureFlagManager->clearFeatureCache($key);
        $this->featureFlagManager->registerFeature($feature);
    }

    /**
     * Atualiza os ambientes para um feature flag
     */

    /**
     * Atualiza as datas de início e término de um feature flag
     */
    public function updateDateRange(string $key, ?Carbon $startsAt = null, ?Carbon $endsAt = null): void
    {
        $feature = $this->getFeatureByKey($key);

        $feature->update([
            'starts_at' => $startsAt,
            'ends_at'   => $endsAt,
        ]);

        $this->featureFlagManager->clearFeatureCache($key);
        $this->featureFlagManager->registerFeature($feature);
    }

    /**
     * Atualiza as variantes de um teste A/B
     */

    /**
     * @param  array<string>  $environments
     */
    public function updateEnvironments(string $key, array $environments): void
    {
        $feature = $this->getFeatureByKey($key);

        $parameters                 = $feature->parameters ?? [];
        $parameters['environments'] = $environments;

        $feature->update([
            'parameters' => $parameters,
        ]);

        $this->featureFlagManager->clearFeatureCache($key);
        $this->featureFlagManager->registerFeature($feature);
    }

    /**
     * @param  array<string, float>  $variants
     */
    public function updateABTest(string $key, array $variants, ?string $defaultVariant = null): void
    {
        $feature = $this->getFeatureByKey($key);

        $parameters             = $feature->parameters ?? [];
        $parameters['variants'] = $variants;

        if ($defaultVariant !== null) {
            $parameters['default_variant'] = $defaultVariant;
        }

        $feature->update([
            'parameters' => $parameters,
        ]);

        $this->featureFlagManager->clearFeatureCache($key);
        $this->featureFlagManager->registerFeature($feature);
    }
}
