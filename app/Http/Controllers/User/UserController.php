<?php declare(strict_types = 1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\{StoreUserRequest, UpdateUserRequest};
use App\Http\Resources\User\{UserCollection, UserResource};
use App\Models\User;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\{JsonResponse};
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Lista todos os usuários do sistema
     *
     * Endpoint: GET /users
     * Grupo: Users
     * Autenticação: Requerida (Sanctum)
     * Middleware: totp.verify, tenant.ratelimit
     *
     * Filtros:
     * - page: Número da página para paginação
     * - per_page: Itens por página
     * - name: Filtrar por nome (correspondência parcial)
     * - email: Filtrar por email (correspondência parcial)
     *
     * Respostas:
     * - 200: Lista paginada de usuários com seus papéis
     * - 401: {"message": "Unauthenticated."}
     * - 403: {"message": "Permission Denied."}
     */
    public function index(): UserCollection
    {
        $users = User::query()
            ->filtrable([
                Filter::like('name', 'name'),
                Filter::like('email', 'email'),
            ])
            ->customPaginate();

        return new UserCollection($users);
    }

    /**
     * Cria um novo usuário
     *
     * Endpoint: POST /users
     * Grupo: Users
     * Autenticação: Requerida (Sanctum)
     * Middleware: totp.verify, tenant.ratelimit
     *
     * Parâmetros:
     * - name: Nome do usuário
     * - email: Email do usuário
     * - password: Senha do usuário
     * - password_confirmation: Confirmação da senha
     *
     * Respostas:
     * - 201: Usuário criado com sucesso
     * - 422: Erros de validação
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Exibe detalhes de um usuário específico
     *
     * Endpoint: GET /users/{user}
     * Grupo: Users
     * Autenticação: Requerida (Sanctum)
     * Middleware: totp.verify, tenant.ratelimit
     *
     * Respostas:
     * - 200: Detalhes do usuário
     * - 404: Usuário não encontrado
     */
    public function show(string $user): UserResource
    {
        $userModel = User::findOrFail($user);

        return new UserResource($userModel);
    }

    /**
     * Atualiza um usuário existente
     *
     * Endpoint: PUT /users/{user}
     * Grupo: Users
     * Autenticação: Requerida (Sanctum)
     * Middleware: totp.verify, tenant.ratelimit
     *
     * Parâmetros:
     * - name: Nome do usuário
     * - email: Email do usuário
     * - (opcional) password: Nova senha
     * - (opcional) password_confirmation: Confirmação da senha
     *
     * Respostas:
     * - 200: Usuário atualizado com sucesso
     * - 404: Usuário não encontrado
     * - 422: Erros de validação
     */
    public function update(UpdateUserRequest $request, string $user): UserResource
    {
        $userModel = User::findOrFail($user);
        $userModel->update($request->validated());

        return new UserResource($userModel);
    }

    /**
     * Remove um usuário do sistema
     *
     * Endpoint: DELETE /users/{user}
     * Grupo: Users
     * Autenticação: Requerida (Sanctum)
     * Middleware: totp.verify, tenant.ratelimit
     *
     * Respostas:
     * - 204: Usuário removido com sucesso (No Content)
     * - 404: Usuário não encontrado
     */
    public function destroy(string $user): JsonResponse
    {
        $userModel = User::findOrFail($user);
        $userModel->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
