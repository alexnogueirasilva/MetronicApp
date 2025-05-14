<?php
declare(strict_types = 1);

namespace App\Http\Middleware\Auth;

use App\Enums\TypeOtp;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTotpVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->otp_method === TypeOtp::TOTP && !$user->totp_verified) {
            return response()->json([
                'message' => 'Two-factor authentication is required.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
