<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\{Impersonation, User};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ImpersonationController extends Controller
{
    /**
     * Inicia uma sessão de impersonation.
     */
    public function start(Request $request, User $user): JsonResponse
    {
        /** @var User $impersonator */
        $impersonator = Auth::user();

        if (!$impersonator->hasPermission('impersonate-users')) {
            return response()->json([
                'message' => 'Você não tem permissão para impersonar outros usuários.',
            ], HttpResponse::HTTP_FORBIDDEN);
        }

        if ($impersonator->id === $user->id) {
            return response()->json([
                'message' => 'Você não pode impersonar a si mesmo.',
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        if ($impersonator->isImpersonating()) {
            return response()->json([
                'message' => 'Você já está impersonando outro usuário. Termine a sessão atual antes de iniciar uma nova.',
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        $impersonation = new Impersonation([
            'id'              => (string) Str::ulid(),
            'impersonator_id' => $impersonator->id,
            'impersonated_id' => $user->id,
        ]);

        $impersonation->save();

        $token = $user->createToken('impersonation-token', ['impersonated'])->plainTextToken;

        return response()->json([
            'message'          => "Você está agora impersonando {$user->name}.",
            'token'            => $token,
            'user'             => $user,
            'impersonation_id' => $impersonation->id,
        ]);
    }

    /**
     * Termina a sessão de impersonation atual.
     */
    public function stop(Request $request): JsonResponse
    {
        /** @var User $impersonator */
        $impersonator = Auth::user();

        $impersonation = $impersonator->activeImpersonation();

        if (!$impersonation) {
            return response()->json([
                'message' => 'Você não está impersonando nenhum usuário.',
            ], HttpResponse::HTTP_BAD_REQUEST);
        }

        $impersonation->end();

        $impersonator->tokens()->where('name', 'impersonation-token')->delete();

        return response()->json([
            'message' => 'Sessão de impersonation encerrada com sucesso.',
        ]);
    }

    /**
     * Lista todas as sessões de impersonation ativas e históricas iniciadas pelo usuário.
     */
    public function history(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasPermission('impersonate-users')) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar o histórico de impersonation.',
            ], HttpResponse::HTTP_FORBIDDEN);
        }

        $impersonations = $user->impersonations()
            ->with('impersonated:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'impersonated_id', 'created_at', 'ended_at']);

        return response()->json([
            'impersonations' => $impersonations,
        ]);
    }
}
