<?php declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\{CreateTenantRequest, UpdateTenantRequest};
use App\Http\Resources\{TenantCollection, TenantResource};
use App\Models\Tenant;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\{JsonResponse};

class TenantController extends Controller
{
    /**
     * Display a listing of tenants.
     */
    public function index(): TenantCollection
    {
        $tenants = Tenant::query()
            ->filtrable([
                Filter::like('name', 'name'),
                Filter::like('domain', 'domain'),
                Filter::exact('is_active', 'active_only'),
                Filter::exact('plan', 'plan'),
            ])
            ->latest()
            ->customPaginate();

        return new TenantCollection($tenants);
    }

    /**
     * Store a newly created tenant.
     */
    public function store(CreateTenantRequest $request): TenantResource
    {
        $tenant = Tenant::create($request->validated());

        return new TenantResource($tenant);
    }

    /**
     * Display the specified tenant.
     */
    public function show(Tenant $tenant): TenantResource
    {
        return new TenantResource($tenant);
    }

    /**
     * Update the specified tenant.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant): TenantResource
    {
        $tenant->update($request->validated());

        return new TenantResource($tenant);
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        // Verificar se o tenant tem usuários
        if ($tenant->users()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir um tenant que possui usuários',
            ], 409);
        }

        $tenant->delete();

        return response()->json([
            'message' => 'Tenant excluído com sucesso',
        ]);
    }
}
