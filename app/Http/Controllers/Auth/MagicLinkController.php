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

        // Sempre envia email, independentemente se o usuário existe ou não
        // Isso mantém consistência e evita vazamento de informação sobre existência de usuários
        SendMagicLinkEmailJob::dispatch($email, $magicLink);

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você receberá um link de acesso em breve.',
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        if (!$request->hasValidSignature()) {
            return $this->invalidLinkResponse();
        }

        $token = $request->token;
        $email = $request->email;

        $otpCode = OtpCode::query()->where('code', $token)
            ->where('email', $email)
            ->where('type', TypeOtp::MAGIC_LINK)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpCode) {
            Log::warning('Magic link inválido ou expirado', ['token' => $token]);

            return $this->invalidLinkResponse();
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            Log::warning('Tentativa de login com magic link para usuário inexistente', ['email' => $email]);

            return $this->userNotFoundResponse();
        }

        Auth::login($user);

        $otpCode->delete();

        $token = $user->createToken('magic-link-' . now()->timestamp)->plainTextToken;

        return $this->successResponse($token, $user);
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
