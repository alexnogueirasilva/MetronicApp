<?php declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\GenerateAuthTokenAction;
use App\Models\User;
use App\Support\SocialiteHelper;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Laravel\Socialite\Two\User as SocialiteUser;
use Log;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends ApiController
{
    public function redirectToProvider(string $provider): JsonResponse
    {
        if (!$this->isValidProvider($provider)) {
            return $this->respondError(
                message: 'Provedor de autenticação não suportado.',
                status: Response::HTTP_BAD_REQUEST
            );
        }

        $driver = SocialiteHelper::getTypedDriver($provider)->stateless();

        $redirectUrl = $driver->redirect()->getTargetUrl();

        return $this->respondSuccess(
            data: ['url' => $redirectUrl],
            message: 'URL de redirecionamento para autenticação.'
        );
    }

    private function isValidProvider(string $provider): bool
    {
        return $provider === 'google';
    }

    public function handleProviderCallback(Request $request, string $provider, GenerateAuthTokenAction $tokenAction): JsonResponse
    {
        if (!$this->isValidProvider($provider)) {
            return $this->respondError(
                message: 'Provedor de autenticação não suportado.',
                status: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $driver = SocialiteHelper::getTypedDriver($provider)->stateless();

            /** @var SocialiteUser $socialiteUser */
            $socialiteUser = $driver->user();

            $providerId = (string) $socialiteUser->getId();
            $name       = $socialiteUser->getName();
            $email      = $socialiteUser->getEmail();
            $avatar     = $socialiteUser->getAvatar();

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

            $token = $tokenAction->execute($user);

            $requiresOtp = $user->otp_method === 'totp' && !$user->totp_verified;

            return $this->respondSuccess(
                data: [
                    'user'         => $user,
                    'token'        => $token,
                    'requires_otp' => $requiresOtp,
                ],
                message: 'Autenticação social realizada com sucesso.'
            );
        } catch (Exception $e) {
            Log::error('Social authentication error: ' . $e->getMessage());

            return $this->respondError(
                message: 'Falha na autenticação social. ' . $e->getMessage(),
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
