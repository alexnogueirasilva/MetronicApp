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
    /**
     * Redirect to social authentication provider
     *
     * This endpoint redirects the user to the specified social authentication provider.
     * Currently supported providers: google.
     *
     * @group Auth
     *
     * @unauthenticated
     *
     * @urlParam provider string required The social authentication provider. Example: google
     *
     * @response 200 {
     *     "status": "success",
     *     "message": "URL de redirecionamento para autenticação.",
     *     "data": {
     *         "url": "https://accounts.google.com/o/oauth2/auth?client_id=..."
     *     },
     *     "meta": {
     *         "api_version": "v1"
     *     }
     * }
     * @response 400 {
     *     "status": "error",
     *     "message": "Provedor de autenticação não suportado."
     * }
     */
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

        $driver      = SocialiteHelper::getTypedDriver($provider)->stateless();
        $redirectUrl = $driver->redirect()->getTargetUrl();

        return redirect()->to($redirectUrl);
    }

    private function isValidProvider(string $provider): bool
    {
        return $provider === 'google';
    }

    /**
     * Handle social authentication callback
     *
     * This endpoint processes the callback from the social authentication provider
     * and logs in the user if authentication is successful.
     *
     * @group Auth
     *
     * @unauthenticated
     *
     * @urlParam provider string required The social authentication provider. Example: google
     *
     * @response {
     *     "user": {
     *         "id": "123e4567-e89b-12d3-a456-426614174000",
     *         "name": "John Doe",
     *         "email": "john@example.com",
     *         "email_verified_at": "2025-05-10T12:00:00.000000Z",
     *         "created_at": "2025-05-01T10:00:00.000000Z",
     *         "updated_at": "2025-05-10T12:00:00.000000Z"
     *     },
     *     "token": "2|laravel_sanctum_token_hash"
     * }
     * @response 500 {
     *     "message": "Falha na autenticação social. Ocorreu um erro ao processar a requisição."
     * }
     */
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
            $nickname   = $socialUser->getName();
            $email      = $socialUser->getEmail();
            $avatar     = $socialUser->getAvatar();

            $nickname = $nickname !== null ? (string) $nickname : null;
            $email    = $email !== null ? (string) $email : null;
            $avatar   = $avatar !== null ? (string) $avatar : null;

            $user = User::findOrCreateSocialUser(
                provider: $provider,
                providerId: $providerId,
                userData: [
                    'nickname' => $nickname,
                    'email'    => $email,
                    'avatar'   => $avatar,
                ]
            );

            Auth::login($user);

            $token = $user->createToken('auth-token')->plainTextToken;

            if ($request->expectsJson() || $request->is('v1/*')) {
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
