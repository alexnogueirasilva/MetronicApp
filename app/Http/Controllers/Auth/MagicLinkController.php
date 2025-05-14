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
     * Solicita o envio de um magic link por email
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
     * Verifica um magic link e autentica o usuário
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
