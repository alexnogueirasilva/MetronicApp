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
     * List all tenants
     *
     * This endpoint returns a paginated list of all tenants in the system.
     * The results can be filtered by name, domain, status, and plan.
     *
     * @group Tenants
     * @authenticated
     * @middleware auth:sanctum
     * @middleware totp.verify
     * @middleware tenant.ratelimit
     *
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page. Example: 15
     * @queryParam name string Filter tenants by name (partial match). Example: Example Corp
     * @queryParam domain string Filter tenants by domain (partial match). Example: example
     * @queryParam active_only boolean Filter only active tenants. Example: true
     * @queryParam plan string Filter tenants by plan type. Example: pro
     *
     * @response {
     *     "data": [
     *         {
     *             "id": "123e4567-e89b-12d3-a456-426614174000",
     *             "name": "Example Corporation",
     *             "domain": "example",
     *             "is_active": true,
     *             "plan": "pro",
     *             "trial_ends_at": "2025-06-01T00:00:00.000000Z",
     *             "created_at": "2025-05-01T10:00:00.000000Z",
     *             "updated_at": "2025-05-01T10:00:00.000000Z"
     *         },
     *         {
     *             "id": "223e4567-e89b-12d3-a456-426614174001",
     *             "name": "Test Company",
     *             "domain": "test",
     *             "is_active": true,
     *             "plan": "basic",
     *             "trial_ends_at": null,
     *             "created_at": "2025-05-02T10:00:00.000000Z",
     *             "updated_at": "2025-05-02T10:00:00.000000Z"
     *         }
     *     ],
     *     "links": {
     *         "first": "http://example.com/api/v1/tenant?page=1",
     *         "last": "http://example.com/api/v1/tenant?page=1",
     *         "prev": null,
     *         "next": null
     *     },
     *     "meta": {
     *         "current_page": 1,
     *         "from": 1,
     *         "last_page": 1,
     *         "path": "http://example.com/api/v1/tenant",
     *         "per_page": 15,
     *         "to": 2,
     *         "total": 2
     *     }
     * }
     *
     * @response 401 {
     *     "message": "Unauthenticated."
     * }
     *
     * @response 403 {
     *     "message": "Permission Denied."
     * }
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
