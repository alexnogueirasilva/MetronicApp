<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\GenerateMagicLinkAction;
use App\Enums\TypeOtp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MagicLinkRequest;
use App\Jobs\Auth\SendMagicLinkEmailJob;
use App\Models\Auth\OtpCode;
use App\Models\User;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Auth, Log};

class MagicLinkController extends Controller
{
    public function request(MagicLinkRequest $request, GenerateMagicLinkAction $action): JsonResponse
    {
        /** @var string $email */
        $email = $request->validated('email');

        $magicLink = $action->execute($email);

        SendMagicLinkEmailJob::dispatch($email, $magicLink);

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você receberá um link de acesso em breve.',
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        if ($request->isMethod('get')) {
            // Validação e conversão de tipos segura
            $email     = is_string($request->email) ? $request->email : '';
            $token     = is_string($request->token) ? $request->token : '';
            $expires   = is_numeric($request->expires) ? (int) $request->expires : 0;
            $signature = is_string($request->signature) ? $request->signature : '';

            // Obter APP_KEY como string de forma segura
            $appKey       = config('app.key');
            $appKeyString = is_string($appKey) ? $appKey : '';

            if ($expires < now()->timestamp) {
                Log::warning('Magic link expirado', ['expires' => $expires]);

                return $this->invalidLinkResponse();
            }

            // Gerar assinatura esperada
            $expectedSignature = hash_hmac('sha256', $email . $token . $expires, $appKeyString);

            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Magic link com assinatura inválida', [
                    'expectedSignature' => $expectedSignature,
                    'receivedSignature' => $signature,
                ]);

                return $this->invalidLinkResponse();
            }
        }

        // Validação e conversão de tipos segura para parâmetros da requisição
        $requestToken = is_string($request->token) ? $request->token : '';
        $email        = is_string($request->email) ? $request->email : '';

        $otpCode = OtpCode::query()
            ->where('code', $requestToken)
            ->where('email', $email)
            ->where('type', TypeOtp::MAGIC_LINK)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpCode) {
            Log::warning('Magic link inválido ou expirado', ['token' => $requestToken]);

            return $this->invalidLinkResponse();
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            Log::warning('Tentativa de login com magic link para usuário inexistente', ['email' => $email]);

            return $this->userNotFoundResponse();
        }

        Auth::login($user);

        $otpCode->delete();

        $authToken = $user->createToken('magic-link-' . now()->timestamp)->plainTextToken;

        return $this->successResponse($authToken, $user);
    }

    private function invalidLinkResponse(): JsonResponse
    {
        return response()->json(['message' => 'Link inválido ou expirado'], 401);
    }

    private function userNotFoundResponse(): JsonResponse
    {
        return response()->json(['message' => 'Usuário não encontrado'], 401);
    }

    private function successResponse(string $token, User $user): JsonResponse
    {
        return response()->json([
            'token'   => $token,
            'message' => 'Login realizado com sucesso',
            'user'    => $user,
        ]);
    }
}
