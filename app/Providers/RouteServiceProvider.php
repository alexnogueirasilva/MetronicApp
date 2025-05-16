<?php

declare(strict_types = 1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{RateLimiter, Route};
use JsonException;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function (): void {
            $this->mapVersionedApiRoutes();

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            $this->mapConsoleRoutes();
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', static fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Mapear as rotas versionadas da API
     * @throws JsonException
     */
    protected function mapVersionedApiRoutes(): void
    {
        $versions = config('api.versions', ['v1']);
        $prefix   = config('api.prefix', 'api');

        $versions = is_array($versions) ? $versions : ['v1'];
        $prefix   = is_string($prefix) ? $prefix : 'api';

        foreach ($versions as $version) {
            $version         = toString($version);
            $versionFilePath = base_path("routes/api/{$version}.php");

            if (file_exists($versionFilePath)) {
                Route::middleware('api')
                    ->prefix("{$prefix}/{$version}")
                    ->name("{$version}.")
                    ->group($versionFilePath);
            }
        }

        Route::middleware(['api', 'api.version'])
            ->prefix($prefix)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Mapear as rotas de console
     */
    protected function mapConsoleRoutes(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/console.php'));
    }
}
