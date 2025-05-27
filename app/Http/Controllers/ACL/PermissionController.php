<?php declare(strict_types = 1);

namespace App\Http\Controllers\ACL;

use App\Http\Controllers\Controller;
use App\Http\Resources\ACL\{GroupedPermissionResource};
use App\Models\Auth\Permission;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Lista todas as permissões do sistema
     *
     * Endpoint: GET /acl/permission
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
     * - 200: Lista paginada de permissões
     * - 401: {"message": "Unauthenticated."}
     * - 403: {"message": "Permission Denied."}
     */
    public function index(): GroupedPermissionResource
    {
        $permissions = Permission::query()
            ->filtrable([
                Filter::like('name', 'name'),
            ])
            ->get();

        return new GroupedPermissionResource($permissions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        //
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
