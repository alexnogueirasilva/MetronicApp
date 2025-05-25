<?php declare(strict_types = 1);

namespace App\Http\Controllers\ACL;

use App\Actions\ACL\CreateRoleWithPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Acl\RoleStoreRequest;
use App\Http\Resources\ACL\{RoleCollection, RoleResource};
use App\Models\Auth\Role;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\Request;
use Throwable;

class RoleController extends Controller
{
    /**
     * Lista todos os papéis (roles) com suas permissões
     *
     * Endpoint: GET /acl/role
     * Grupo: ACL
     * Autenticação: Requerida (Sanctum)
     * Middleware: totp.verify
     *
     * Filtros:
     * - page: Número da página para paginação
     * - per_page: Itens por página
     * - name: Filtrar por nome (correspondência parcial)
     *
     * Respostas:
     * - 200: Lista paginada de papéis com suas permissões
     * - 401: {"message": "Unauthenticated."}
     * - 403: {"message": "Permission Denied."}
     */
    public function index(): RoleCollection
    {
        $roles = Role::query()
            ->filtrable([
                Filter::like('name', 'name'),
                Filter::relationship('permissions', 'name', 'LIKE', 'permissions'),
            ])
            ->customPaginate();

        return new RoleCollection($roles);
    }

    /**
     * Cria um novo papel (role) com permissões associadas
     *
     * Endpoint: POST /acl/role
     * Grupo: ACL
     * Autenticação: Requerida (Sanctum)
     * Middleware: totp.verify
     *
     * Requisição:
     * - name: Nome do papel (string, obrigatório)
     * - description: Descrição do papel (string, opcional)
     * - icon: Ícone do papel (string, opcional)
     * - permissions: IDs das permissões associadas (array de inteiros ou strings, opcional)
     *
     * Respostas:
     * - 201: Papel criado com sucesso
     * - 422: {"message": "Validation Error", "errors": {...}}
     * - 401: {"message": "Unauthenticated."}
     * - 403: {"message": "Permission Denied."}
     * - 500: {"message": "Internal Server Error"}
     *
     * @throws Throwable
     */
    public function store(RoleStoreRequest $request, CreateRoleWithPermissions $withPermissions): RoleResource
    {
        return new RoleResource(
            $withPermissions->execute($request->toDto())
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): void
    {
        //
    }
}
