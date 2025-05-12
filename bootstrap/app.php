<?php declare(strict_types = 1);

use App\Exceptions\CustomNotFoundException;
use App\Http\Middleware\Auth\EnsureTotpVerified;
use App\Http\Middleware\RateLimit\{EndpointRateLimiter, TenantRateLimiter};
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

Model::unguard();
Date::use(CarbonImmutable::class);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->prefix('v1')
                ->group(base_path('routes/api.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'totp.verify'        => EnsureTotpVerified::class,
            'tenant.ratelimit'   => TenantRateLimiter::class,
            'endpoint.ratelimit' => EndpointRateLimiter::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(fn (NotFoundHttpException $e): JsonResponse => CustomNotFoundException::fromNotFoundHttpException($e)->render(request()));
    })->create();
