<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginAction;
use App\DTO\Auth\LoginDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * Autenticação de usuário com email e senha
     *
     * Endpoint: POST /auth/login
     * Grupo: Auth
     * Autenticação: Não requerida
     *
     * Parâmetros:
     * - email (string): Email do usuário
     * - password (string): Senha do usuário
     * - device (string, opcional): Nome do dispositivo
     *
     * Respostas:
     * - 200: {"token": "string", "user": {...}}
     * - 401: {"message": "Credentials do not match"}
     * - 422: {"message": "The given data was invalid", "errors": {...}}
     */
    public function __invoke(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $dto = LoginDTO::fromRequest((array)$request->toDTO());

        return $action->execute($dto);
    }
}
