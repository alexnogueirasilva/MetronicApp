<?php
declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{Impersonation, User};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating another user
     *
     * This endpoint allows an admin to impersonate another user in the system.
     * It requires the 'impersonate-users' permission.
     *
     * @group Auth
     *
     * @authenticated
     *
     * @middleware auth:sanctum
     * @middleware totp.verify
     *
     * @urlParam user string required The ID of the user to impersonate. Example: 123e4567-e89b-12d3-a456-426614174000
     *
     * @response {
     *     "message": "Você está agora impersonando John Doe.",
     *     "token": "3|impersonation_token_hash",
     *     "user": {
     *         "id": "123e4567-e89b-12d3-a456-426614174000",
     *         "name": "John Doe",
     *         "email": "john@example.com",
     *         "email_verified_at": "2025-05-10T12:00:00.000000Z",
     *         "created_at": "2025-05-01T10:00:00.000000Z",
     *         "updated_at": "2025-05-10T12:00:00.000000Z"
     *     },
     *     "impersonation_id": "01HZ2XABCDEF1234567890ABCDE"
     * }
     * @response 403 {
     *     "message": "Você não tem permissão para impersonar outros usuários."
     * }
     * @response 400 {
     *     "message": "Você não pode impersonar a si mesmo."
     * }
     * @response 400 {
     *     "message": "Você já está impersonando outro usuário. Termine a sessão atual antes de iniciar uma nova."
     * }
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
            'message'          => "Você está agora impersonando {$user->last_name}.",
            'token'            => $token,
            'user'             => $user,
            'impersonation_id' => $impersonation->id,
        ]);
    }

    /**
     * Stop impersonating another user
     *
     * This endpoint ends the current impersonation session and revokes
     * the impersonation token.
     *
     * @group Auth
     *
     * @authenticated
     *
     * @middleware auth:sanctum
     *
     * @response {
     *     "message": "Sessão de impersonation encerrada com sucesso."
     * }
     * @response 400 {
     *     "message": "Você não está impersonando nenhum usuário."
     * }
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
     * Get impersonation history
     *
     * This endpoint returns a list of all impersonation sessions started by the
     * current user, both active and historical. It requires the 'impersonate-users' permission.
     *
     * @group Auth
     *
     * @authenticated
     *
     * @middleware auth:sanctum
     * @middleware totp.verify
     *
     * @response {
     *     "impersonations": [
     *         {
     *             "id": "01HZ2XABCDEF1234567890ABCDE",
     *             "impersonated_id": "123e4567-e89b-12d3-a456-426614174000",
     *             "created_at": "2025-05-10T12:00:00.000000Z",
     *             "ended_at": "2025-05-10T12:30:00.000000Z",
     *             "impersonated": {
     *                 "id": "123e4567-e89b-12d3-a456-426614174000",
     *                 "name": "John Doe",
     *                 "email": "john@example.com"
     *             }
     *         },
     *         {
     *             "id": "01HZ2XABCDEF1234567890ABCDF",
     *             "impersonated_id": "223e4567-e89b-12d3-a456-426614174001",
     *             "created_at": "2025-05-09T10:00:00.000000Z",
     *             "ended_at": "2025-05-09T10:45:00.000000Z",
     *             "impersonated": {
     *                 "id": "223e4567-e89b-12d3-a456-426614174001",
     *                 "name": "Jane Smith",
     *                 "email": "jane@example.com"
     *             }
     *         }
     *     ]
     * }
     * @response 403 {
     *     "message": "Você não tem permissão para visualizar o histórico de impersonation."
     * }
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
            ->with('impersonated:id,first_name,last_name,email')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'impersonated_id', 'created_at', 'ended_at']);

        return response()->json([
            'impersonations' => $impersonations,
        ]);
    }
}
