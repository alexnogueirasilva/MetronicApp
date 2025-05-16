<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SocialiteHelper;
use Exception;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Two\User as SocialiteUser;
use Log;
use Symfony\Component\HttpFoundation\Response;

class SocialAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse|JsonResponse
    {
        if (!$this->isValidProvider($provider)) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Provedor de autenticação não suportado.',
                ], 400);
            }

            return redirect()->back()->with('error', 'Provedor de autenticação inválido.');
        }

        // API flow
        if (request()->expectsJson() || request()->is('api/*')) {
            $driver      = SocialiteHelper::getTypedDriver($provider)->stateless();
            $redirectUrl = $driver->redirect()->getTargetUrl();

            return response()->json([
                'status'  => 'success',
                'message' => 'URL de redirecionamento para autenticação.',
                'data'    => ['url' => $redirectUrl],
                'meta'    => ['api_version' => 'v1'],
            ]);
        }

        // Web flow
        $driver      = SocialiteHelper::getTypedDriver($provider)->stateless();
        $redirectUrl = $driver->redirect()->getTargetUrl();

        return redirect()->to($redirectUrl);
    }

    private function isValidProvider(string $provider): bool
    {
        return $provider === 'google';
    }

    public function callback(Request $request, string $provider): JsonResponse|RedirectResponse
    {
        if (!$this->isValidProvider($provider)) {
            return redirect()->back()->with('error', 'Provedor de autenticação inválido.');
        }

        try {
            $driver = SocialiteHelper::getTypedDriver($provider)->stateless();

            /** @var SocialiteUser $socialUser */
            $socialUser = $driver->user();

            $providerId = (string) $socialUser->getId();
            $name       = $socialUser->getName();
            $email      = $socialUser->getEmail();
            $avatar     = $socialUser->getAvatar();

            $name   = $name !== null ? (string) $name : null;
            $email  = $email !== null ? (string) $email : null;
            $avatar = $avatar !== null ? (string) $avatar : null;

            $user = User::findOrCreateSocialUser(
                provider: $provider,
                providerId: $providerId,
                userData: [
                    'name'   => $name,
                    'email'  => $email,
                    'avatar' => $avatar,
                ]
            );

            Auth::login($user);

            $token = $user->createToken('auth-token')->plainTextToken;

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'user'  => $user,
                    'token' => $token,
                ]);
            }

            $frontendUrl = config('app.frontend_url');
            $frontendUrl = is_string($frontendUrl) ? $frontendUrl : '';

            return redirect()->intended($frontendUrl . '/dashboard')
                ->with('token', $token);
        } catch (Exception $e) {
            Log::error('Social authentication error: ' . $e->getMessage());

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Falha na autenticação social. ' . $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return redirect()->route('login')
                ->with('error', 'Falha na autenticação social. Por favor, tente novamente.');
        }
    }
}
