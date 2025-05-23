<?php declare(strict_types = 1);

namespace App\Http\Controllers\ACL;

use App\Http\Controllers\Controller;
use App\Http\Requests\Acl\RoleStoreRequest;
use App\Http\Resources\ACL\{RoleCollection, RoleResource};
use App\Models\Auth\Role;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\Request;

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
     * Store a newly created resource in storage.
     */
    public function store(RoleStoreRequest $request): RoleResource
    {
        $role = Role::query()->create($request->validated());

        return new RoleResource($role);
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
