<?php declare(strict_types = 1);

namespace App\Http\Middleware\RateLimit;

use App\Enums\PlanType;
use App\Traits\Auth\AuthenticatedUser;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\{Exceptions\ThrottleRequestsException, JsonResponse, Request};
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TenantRateLimiter
{
    use AuthenticatedUser;
    /**
     * Create a new rate limiter middleware.
     *
     * @return void
     */
    public function __construct(protected RateLimiter $limiter) {}

    /**
     * Handle an incoming request.
     *
     *
     * @throws ThrottleRequestsException
     */
    public function handle(Request $request, Closure $next, int|string|null $maxAttempts = null): SymfonyResponse
    {

        $user = $this->getAuthenticatedUser();

        $tenant = $user->tenant;

        $maxAttemptValue = $maxAttempts ?? $user->getRateLimitPerMinute();

        if ($tenant && $tenant->plan === PlanType::UNLIMITED) {
            /** @var SymfonyResponse $response */
            $response = $next($request);

            return $response;
        }

        $key = $tenant
            ? "{$tenant->getRateLimitCacheKey()}:{$user->getRateLimitCacheKey()}"
            : $user->getRateLimitCacheKey();

        $decaySeconds = 60;

        $this->limiter->hit($key, $decaySeconds);

        /** @var int $attempts */
        $attempts = $this->limiter->attempts($key);

        /** @var SymfonyResponse $response */
        $response = $next($request);

        return $this->addHeaders(
            $response,
            toInteger($maxAttemptValue),
            $this->calculateRemainingAttempts($key, toInteger($maxAttemptValue), $attempts)
        );
    }

    /**
     * Add the limit header information to the given response.
     */
    protected function addHeaders(SymfonyResponse $response, int $maxAttempts, int $remainingAttempts, ?int $retryAfter = null): SymfonyResponse
    {
        $response->headers->set(
            'X-RateLimit-Limit',
            (string) $maxAttempts,
            false
        );

        $response->headers->set(
            'X-RateLimit-Remaining',
            (string) $remainingAttempts,
            false
        );

        if ($retryAfter !== null && $retryAfter !== 0) {
            $response->headers->set(
                'Retry-After',
                (string) $retryAfter,
                false
            );

            $response->headers->set(
                'X-RateLimit-Reset',
                (string) $this->availableAt($retryAfter),
                false
            );
        }

        return $response;
    }

    /**
     * Get the time (in seconds) at which the rate limit will be available.
     */
    protected function availableAt(int $delay): int
    {
        return time() + $delay;
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts, ?int $attempts = null): int
    {
        if ($attempts === null) {
            /** @var int $limiterAttempts */
            $limiterAttempts = $this->limiter->attempts($key);
            $attempts        = $limiterAttempts;
        }

        return $maxAttempts - $attempts;
    }

    /**
     * Handle rate limiting for anonymous requests
     */
    protected function handleAnonymousRequest(Request $request, Closure $next): SymfonyResponse
    {
        $key          = 'ip:' . $request->ip();
        $maxAttempts  = 15;
        $decaySeconds = 60;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decaySeconds);

        /** @var SymfonyResponse $response */
        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Create a 'too many attempts' response
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): JsonResponse
    {
        $retryAfter = $this->limiter->availableIn($key);

        $headers = [
            'Retry-After'           => $retryAfter,
            'X-RateLimit-Limit'     => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset'     => $this->availableAt($retryAfter),
        ];

        return response()->json([
            'message'     => 'Too Many Requests',
            'retry_after' => $retryAfter,
        ], 429, $headers);
    }
}
