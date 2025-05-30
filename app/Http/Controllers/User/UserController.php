<?php declare(strict_types = 1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\{StoreUserRequest, UpdateUserRequest};
use App\Http\Resources\User\{UserCollection, UserMeResource};
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
            ->with(['role.permissions'])
            ->filtrable([
                Filter::like('nickname', 'nickname'),
                Filter::like('email', 'email'),
            ])
            ->customPaginate(data: ['per_page' => 10]);

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
        $user = User::query()->create($request->validated());
        $user->load(['role.permissions']);

        return new UserMeResource($user)
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
    public function show(string $user): UserMeResource
    {
        $userModel = User::with(['role.permissions'])->findOrFail($user);

        return new UserMeResource($userModel);
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
    public function update(UpdateUserRequest $request, string $user): UserMeResource
    {
        $userModel = User::findOrFail($user);
        $userModel->update($request->validated());
        $userModel->load(['role.permissions']);

        return new UserMeResource($userModel);
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
