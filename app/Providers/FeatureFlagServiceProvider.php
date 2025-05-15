<?php declare(strict_types = 1);

namespace App\Providers;

use App\Models\{Tenant, User};
use App\Services\FeatureFlags\FeatureFlagManager;
use Blade;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\{Feature, PennantServiceProvider};
use Throwable;

class FeatureFlagServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(FeatureFlagManager::class, function ($app): FeatureFlagManager {
            /** @var Application $app */
            return new FeatureFlagManager(
                $app->make('cache.store')
            );
        });

        $this->app->afterResolving(PennantServiceProvider::class, function (): void {
            $this->registerDefaultResolvers();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar all feature flags no boot - skip in console and testing
        if (!$this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            try {
                $featureFlagManager = $this->app->make(FeatureFlagManager::class);
                $featureFlagManager->registerFeatureFlags();
            } catch (Throwable $e) {
                // Log error but don't crash the application
                logger()->error('Failed to register feature flags', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Register Blade directives
        $this->registerBladeDirectives();
    }

    /**
     * Register the default resolvers for tenant and user types
     */
    private function registerDefaultResolvers(): void
    {
        // When a feature flag is checked without a scope, use the current user
        Feature::resolveScopeUsing(function (?string $driver) {
            // Handle tenant-specific features
            if ($driver === 'tenant' && auth()->check()) {
                /** @var User|null $user */
                $user = auth()->user();

                return $user?->tenant;
            }

            // By default, use the current user if authenticated
            return auth()->user();
        });

        // Special handling for scopes in Laravel Pennant
        // We just register our scope resolver function which handles Tenant and User
        // so we don't need to call define() directly, which is not supported
    }

    /**
     * Register custom blade directives for feature flags
     *
     * Note: This API project doesn't use Blade views, but these directives
     * are registered for completeness and potential future use cases.
     */
    private function registerBladeDirectives(): void
    {
        // No directives needed for API-only project
    }
}
