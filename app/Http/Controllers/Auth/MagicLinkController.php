<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\{GenerateAuthTokenAction, GenerateMagicLinkAction, VerifyMagicLinkAction};
use App\Enums\QueueEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MagicLinkRequest;
use App\Jobs\Auth\SendMagicLinkEmailJob;
use App\Models\User;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Log;

/**
 * Controller para autenticação via Magic Link
 *
 * Este controller gerencia a emissão e validação de links mágicos
 * para autenticação sem senha.
 */
class MagicLinkController extends Controller
{
    /**
     * Request a magic link for passwordless login
     *
     * This endpoint generates and sends a magic link to the provided email address.
     * For security reasons, it always returns success even if the email is not registered.
     *
     * @group Auth
     * @unauthenticated
     *
     * @bodyParam email string required The email address to send the magic link to. Example: user@example.com
     *
     * @response {
     *     "message": "Se o e-mail estiver cadastrado, você receberá um link de acesso em breve."
     * }
     *
     * @response 422 {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "email": [
     *             "The email field is required.",
     *             "The email must be a valid email address."
     *         ]
     *     }
     * }
     */
    public function request(
        MagicLinkRequest $request,
        GenerateMagicLinkAction $generateAction
    ): JsonResponse {
        /** @var string $email */
        $email = $request->validated('email');

        $magicLink = $generateAction->execute($email);

        SendMagicLinkEmailJob::dispatch($email, $magicLink)
            ->onQueue(QueueEnum::AUTH_DEFAULT->value);

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você receberá um link de acesso em breve.',
        ]);
    }

    /**
     * Verify a magic link and authenticate user
     *
     * This endpoint validates a magic link token and authenticates the user if successful.
     * It supports both GET and POST methods to accommodate different verification flows.
     *
     * @group Auth
     * @unauthenticated
     *
     * @bodyParam email string required The email address associated with the magic link. Example: user@example.com
     * @bodyParam token string required The magic link token to verify. Example: 9a52c417d4b8bcb1be1e9969
     * @queryParam email string When using GET method, the email address. Example: user@example.com
     * @queryParam token string When using GET method, the magic link token. Example: 9a52c417d4b8bcb1be1e9969
     * @queryParam expires integer When using GET method, the expiration timestamp. Example: 1715000000
     * @queryParam signature string When using GET method, the verification signature. Example: a9b8c7d6e5f4g3h2i1j
     *
     * @response {
     *     "token": "2|laravel_sanctum_token_hash",
     *     "message": "Login realizado com sucesso",
     *     "user": {
     *         "id": "123e4567-e89b-12d3-a456-426614174000",
     *         "name": "John Doe",
     *         "email": "john@example.com",
     *         "email_verified_at": "2025-05-10T12:00:00.000000Z",
     *         "created_at": "2025-05-01T10:00:00.000000Z",
     *         "updated_at": "2025-05-10T12:00:00.000000Z",
     *         "roles": [
     *             {
     *                 "id": 1,
     *                 "name": "admin"
     *             }
     *         ]
     *     }
     * }
     *
     * @response 401 {
     *     "message": "Link inválido ou expirado"
     * }
     *
     * @response 500 {
     *     "message": "Ocorreu um erro ao processar a solicitação"
     * }
     */
    public function verify(
        Request $request,
        VerifyMagicLinkAction $verifyAction,
        GenerateAuthTokenAction $tokenAction
    ): JsonResponse {
        try {
            $email = is_string($request->email) ? $request->email : '';
            $token = is_string($request->token) ? $request->token : '';

            if ($request->isMethod('get')) {
                $expires   = is_numeric($request->expires) ? (int) $request->expires : 0;
                $signature = is_string($request->signature) ? $request->signature : '';

                if (!$verifyAction->verifySignature($email, $token, $expires, $signature)) {
                    return $this->invalidLinkResponse();
                }
            }

            $user = $verifyAction->verifyTokenAndAuthenticate($email, $token);

            $authToken = $tokenAction->execute($user, 'magic-link');

            return $this->successResponse($authToken, $user);

        } catch (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        } catch (Exception $e) {
            Log::error('Erro ao verificar magic link', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Ocorreu um erro ao processar a solicitação'], 500);
        }
    }

    /**
     * Resposta para link inválido ou expirado
     */
    private function invalidLinkResponse(): JsonResponse
    {
        return response()->json(['message' => 'Link inválido ou expirado'], 401);
    }

    /**
     * Resposta para autenticação bem-sucedida
     */
    private function successResponse(string $token, User $user): JsonResponse
    {
        return response()->json([
            'token'   => $token,
            'message' => 'Login realizado com sucesso',
            'user'    => $user,
        ]);
    }
}
