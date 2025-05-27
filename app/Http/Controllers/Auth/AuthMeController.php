<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthMeAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserMeResource;

class AuthMeController extends Controller
{
    /**
     * Retorna dados do usuário autenticado
     *
     * Endpoint: GET /auth/me
     * Grupo: Auth
     * Autenticação: Requerida (Sanctum)
     * Middleware: totp.verify
     *
     * Respostas:
     * - 200: Dados do usuário com suas roles e permissões
     * - 401: {"message": "Unauthenticated."}
     * - 403: {"message": "TOTP not verified."}
     */
    public function __invoke(AuthMeAction $action): UserMeResource
    {
        return new UserMeResource($action->execute());
    }
}
