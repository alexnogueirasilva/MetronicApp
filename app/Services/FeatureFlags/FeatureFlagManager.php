<?php declare(strict_types = 1);

namespace App\Services\FeatureFlags;

use App\Enums\FeatureFlagType;
use App\Models\{FeatureFlag, Tenant, User};
use Illuminate\Cache\Repository as Cache;
use Illuminate\Support\Facades\{DB, Log};
use Laravel\Pennant\{Feature};
use Throwable;

class FeatureFlagManager
{
    public function __construct(
        protected Cache $cache,
    ) {
        //
    }

    /**
     * Registra as feature flags no Pennant
     */
    public function registerFeatureFlags(): void
    {
        try {
            $features = FeatureFlag::query()->where('is_active', true)->get();

            foreach ($features as $feature) {
                $this->registerFeature($feature);
            }
        } catch (Throwable $e) {
            Log::error('Erro ao registrar feature flags', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Registra uma feature flag específica
     */
    public function registerFeature(FeatureFlag $feature): void
    {
        if (!$feature->is_active) {
            return;
        }

        if (!$feature->isWithinDateRange()) {
            return;
        }

        if (!$feature->isCompatibleWithEnvironment()) {
            return;
        }

        $featureName = $feature->feature_name;

        switch ($feature->type) {
            case FeatureFlagType::GLOBAL:
                $this->registerGlobalFeature($featureName, $feature->default_value);

                break;

            case FeatureFlagType::PER_TENANT:
                $this->registerTenantFeature($featureName, $feature);

                break;

            case FeatureFlagType::PER_USER:
                $this->registerUserFeature($featureName, $feature);

                break;

            case FeatureFlagType::PERCENTAGE:
                $this->registerPercentageFeature($featureName, $feature);

                break;

            case FeatureFlagType::DATE_RANGE:
                $this->registerDateRangeFeature($featureName, $feature);

                break;

            case FeatureFlagType::ENVIRONMENT:
                $this->registerEnvironmentFeature($featureName, $feature);

                break;

            case FeatureFlagType::AB_TEST:
                $this->registerABTestFeature($featureName, $feature);

                break;
        }
    }

    /**
     * Registra uma feature global
     */
    protected function registerGlobalFeature(string $featureName, bool $value): void
    {
        Feature::define($featureName, fn (): bool => $value);
    }

    /**
     * Registra uma feature por tenant
     */
    protected function registerTenantFeature(string $featureName, FeatureFlag $feature): void
    {
        Feature::define($featureName, function (?Tenant $tenant) use ($feature, $featureName) {
            if (!$tenant instanceof Tenant) {
                return $feature->default_value;
            }

            $cacheKey = "feature:{$featureName}:tenant:{$tenant->id}";

            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }

            $tenantFeature = DB::table('feature_flag_tenant')
                ->where('feature_name', $featureName)
                ->where('tenant_id', $tenant->id)
                ->first();

            if ($tenantFeature) {
                $value = isset($tenantFeature->value) && (bool) $tenantFeature->value;

                if (isset($tenantFeature->expires_at) && $tenantFeature->expires_at !== null) {
                    $expiresAt = is_string($tenantFeature->expires_at)
                        ? $tenantFeature->expires_at
                        : date('Y-m-d H:i:s', (int)strtotime(toString($tenantFeature->expires_at)));

                    if (now()->gt($expiresAt)) {
                        $value = $feature->default_value;
                    }
                }

                $this->cache->put($cacheKey, $value, now()->addMinutes(30));

                return $value;
            }

            $this->cache->put($cacheKey, $feature->default_value, now()->addMinutes(30));

            return $feature->default_value;
        });
    }

    /**
     * Registra uma feature por usuário
     */
    protected function registerUserFeature(string $featureName, FeatureFlag $feature): void
    {
        Feature::define($featureName, function (?User $user) use ($feature, $featureName) {
            if (!$user instanceof User) {
                return $feature->default_value;
            }

            $cacheKey = "feature:{$featureName}:user:{$user->id}";

            if ($this->cache->has($cacheKey)) {
                return $this->cache->get($cacheKey);
            }

            $userFeature = DB::table('feature_flag_user')
                ->where('feature_name', $featureName)
                ->where('user_id', $user->id)
                ->first();

            if ($userFeature) {
                $value = isset($userFeature->value) && (bool) $userFeature->value;

                // Verificar se expirou
                if (isset($userFeature->expires_at) && $userFeature->expires_at !== null) {
                    $expiresAt = is_string($userFeature->expires_at)
                        ? $userFeature->expires_at
                        : date('Y-m-d H:i:s', (int)strtotime(toString($userFeature->expires_at)));

                    if (now()->gt($expiresAt)) {
                        $value = $feature->default_value;
                    }
                }

                $this->cache->put($cacheKey, $value, now()->addMinutes(30));

                return $value;
            }

            $this->cache->put($cacheKey, $feature->default_value, now()->addMinutes(30));

            return $feature->default_value;
        });
    }

    /**
     * Registra uma feature baseada em porcentagem
     */
    protected function registerPercentageFeature(string $featureName, FeatureFlag $feature): void
    {
        $percentage = isset($feature->parameters['percentage']) ? toInteger(($feature->parameters['percentage'])) : 0;
        $odds       = min(max($percentage, 0), 100) / 100.0;

        Feature::define($featureName, function ($scope) use ($odds, $feature, $featureName) {
            if (!$scope) {
                return $feature->default_value;
            }

            $scopeId = $this->getScopeId($scope);

            $seed  = md5($featureName . $scopeId);
            $value = hexdec(substr($seed, 0, 8)) / 0xffffffff;

            return $value <= $odds;
        });
    }

    /**
     * Obtém um ID consistente para o escopo, independente do tipo
     */
    protected function getScopeId(mixed $scope): string
    {
        if ($scope instanceof User) {
            return 'user_' . $scope->id;
        }

        if ($scope instanceof Tenant) {
            return 'tenant_' . $scope->id;
        }

        if (is_object($scope) && method_exists($scope, 'getKey')) {
            $key = $scope->getKey();

            return $scope::class . '_' . (is_scalar($key) ? (string)$key : 'object');
        }

        if (is_scalar($scope)) {
            return (string) $scope;
        }

        return md5(serialize($scope));
    }

    /**
     * Registra uma feature por período de tempo
     */
    protected function registerDateRangeFeature(string $featureName, FeatureFlag $feature): void
    {
        Feature::define($featureName, fn (): bool => $feature->isWithinDateRange());
    }

    /**
     * Registra uma feature por ambiente
     */
    protected function registerEnvironmentFeature(string $featureName, FeatureFlag $feature): void
    {
        Feature::define($featureName, static fn (): bool => $feature->isCompatibleWithEnvironment());
    }

    /**
     * Registra uma feature para teste A/B
     */
    protected function registerABTestFeature(string $featureName, FeatureFlag $feature): void
    {
        $variants       = $feature->parameters['variants'] ?? [];
        $defaultVariant = $feature->parameters['default_variant'] ?? null;

        Feature::define($featureName, function ($scope) use ($variants, $defaultVariant, $featureName) {
            if (!$scope || empty($variants)) {
                return $defaultVariant;
            }

            $scopeId = $this->getScopeId($scope);

            $seed  = md5($featureName . $scopeId);
            $value = hexdec(substr($seed, 0, 8)) / 0xffffffff;

            $total = 0;

            if (is_array($variants)) {
                foreach ($variants as $variant => $weight) {
                    $total += is_numeric($weight) ? (float)$weight : 0.0;

                    if ($value <= $total) {
                        return $variant;
                    }
                }
            }

            return $defaultVariant;
        });
    }

    /**
     * Limpa o cache de uma feature específica
     */
    public function clearFeatureCache(string $featureName): void
    {
        $this->cache->forget("feature:{$featureName}");
    }

    /**
     * Limpa todo o cache de features
     */
    public function clearAllFeatureCache(): void
    {
        $this->cache->getStore()->flush();
    }
}
