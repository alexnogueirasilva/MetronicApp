<?php declare(strict_types = 1);

namespace App\Http\Middleware\RateLimit;

use App\Models\{Tenant, User};
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EndpointRateLimiter
{
    /**
     * Endpoint rate limit definitions.
     *
     * Maps endpoint patterns to their rate limits.
     * Format: 'endpoint_pattern' => [limit_multiplier, decay_minutes]
     *
     * @var array<string, array{float, int}>
     */
    protected $endpointLimits = [
        // Login has stricter rate limits (0.2x the user's rate limit)
        'api/auth/login' => [0.2, 5],

        // Password reset endpoints have stricter limits
        'api/auth/forgot-password' => [0.1, 15],
        'api/auth/reset-password'  => [0.1, 15],

        // OTP endpoints have stricter limits
        'api/auth/otp/*' => [0.2, 5],

        // Data-heavy endpoints get reduced limits
        'api/reports/*' => [0.5, 1],
        'api/exports/*' => [0.3, 5],

        // Default - full rate limit
        '*' => [1.0, 1],
    ];

    /**
     * Create a new endpoint rate limiter middleware.
     *
     * @return void
     */
    public function __construct(protected RateLimiter $limiter) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        // Get the authenticated user
        /** @var User|null */
        $user = Auth::user();

        // If no user, use IP-based rate limiting
        if (!$user) {
            /** @var SymfonyResponse $response */
            $response = $next($request);

            return $response;
        }

        // Base rate limit from user/tenant
        $baseRateLimit = $user->getRateLimitPerMinute();

        // Get the endpoint pattern that matches the current request
        $path            = $request->path();
        $endpointPattern = $this->getMatchingEndpointPattern($path);

        // Get the rate limit details for this endpoint
        [$multiplier, $decayMinutes] = $this->endpointLimits[$endpointPattern];

        // Calculate the adjusted rate limit for this endpoint
        $maxAttempts = (int) ceil($baseRateLimit * $multiplier);

        // Enforce a minimum rate limit of 1
        $maxAttempts = max(1, $maxAttempts);

        // Decay in seconds
        $decaySeconds = $decayMinutes * 60;

        // Create a specific key for this endpoint and user
        $key = 'endpoint:' . $endpointPattern . ':user:' . $user->id;

        // Check if too many attempts
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        // Increment the counter
        $this->limiter->hit($key, $decaySeconds);

        /** @var SymfonyResponse $response */
        $response = $next($request);

        // Add headers
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts),
            $this->limiter->availableIn($key)
        );
    }

    /**
     * Get the matching endpoint pattern for the given path.
     */
    protected function getMatchingEndpointPattern(string $path): string
    {
        foreach ($this->endpointLimits as $pattern => $limits) {
            if ($pattern === '*') {
                continue;
            }

            // Convert endpoint pattern to regex
            $regex = $this->patternToRegex($pattern);

            if (preg_match($regex, $path)) {
                return $pattern;
            }
        }

        // Default pattern
        return '*';
    }

    /**
     * Convert an endpoint pattern to a regex.
     */
    protected function patternToRegex(string $pattern): string
    {
        // Escape special regex characters
        $pattern = preg_quote($pattern, '/');

        // Convert wildcard * to regex equivalent
        $pattern = str_replace('\*', '.*', $pattern);

        // Add start/end anchors
        return '/^' . $pattern . '$/';
    }

    /**
     * Create a 'too many attempts' response.
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
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        // Garantir que o valor retornado por attempts() seja um inteiro
        $attempts = toInteger($this->limiter->attempts($key));

        return $maxAttempts - $attempts;
    }

    /**
     * Get the time (in seconds) at which the rate limit will be available.
     */
    protected function availableAt(int $delay): int
    {
        return time() + $delay;
    }
}
