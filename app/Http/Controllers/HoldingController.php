<?php
declare(strict_types = 1);

namespace App\Http\Controllers;

use App\Enums\AuthorizationEnum;
use App\Exceptions\Authorize\AuthorizationException;
use App\Helpers\CnpjHelper;
use App\Http\Requests\Holding\{CreateHoldingRequest, UpdateHoldingRequest, UploadLogoRequest};
use App\Http\Resources\{Holding\HoldingCollection, Holding\HoldingResource};
use App\Models\Holding;
use App\Services\Authorize\AuthorizeAccount;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{DB, Storage};
use JsonException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HoldingController extends Controller
{
    /**
     * List all holdings
     *
     * This endpoint returns a paginated list of all holdings in the system.
     * The results can be filtered by name, CNPJ, status, and other criteria.
     *
     * @group Holdings
     *
     * @authenticated
     *
     * @middleware auth:sanctum
     * @middleware totp.verify
     * @middleware tenant.ratelimit
     *
     * @queryParam page integer Page number for pagination. Example: 1
     * @queryParam per_page integer Number of items per page. Example: 15
     * @queryParam name string Filter holdings by name (partial match). Example: Holding Corp
     * @queryParam tax_id string Filter holdings by CNPJ (partial match). Example: 12345678
     * @queryParam active_only boolean Filter only active holdings. Example: true
     * @queryParam with_logo boolean Filter holdings with logo. Example: true
     *
     * @response {
     *     "data": [
     *         {
     *             "id": "01HZ2XABCDEF1234567890ABCDE",
     *             "name": "Example Holding Corp",
     *             "legal_name": "Example Holding Corporation S.A.",
     *             "tax_id": "12.345.678/0001-90",
     *             "email": "contact@example.com",
     *             "phone": "+55 11 99999-9999",
     *             "description": "Leading holding company...",
     *             "logo_url": null,
     *             "full_address": "Rua Example, 123, Centro, São Paulo, SP, 01234-567",
     *             "is_active": true,
     *             "created_at": "2025-05-01T10:00:00.000000Z",
     *             "updated_at": "2025-05-01T10:00:00.000000Z"
     *         }
     *     ],
     *     "links": {
     *         "first": "http://example.com/api/v1/holdings?page=1",
     *         "last": "http://example.com/api/v1/holdings?page=1",
     *         "prev": null,
     *         "next": null
     *     },
     *     "meta": {
     *         "current_page": 1,
     *         "from": 1,
     *         "last_page": 1,
     *         "path": "http://example.com/api/v1/holdings",
     *         "per_page": 15,
     *         "to": 1,
     *         "total": 1
     *     }
     * }
     */
    public function index(): HoldingCollection
    {
        $holdings = Holding::query()
            ->with(['tenant'])
            ->filtrable([
                Filter::like('name', 'name'),
                Filter::like('legal_name', 'legal_name'),
                Filter::like('tax_id', 'tax_id'),
                Filter::exact('is_active', 'active_only'),
            ])
            ->latest()
            ->customPaginate();

        return new HoldingCollection($holdings);
    }

    /**
     * Display the specified holding.
     * @throws AuthorizationException
     */
    public function show(Holding $holding): HoldingResource
    {
        AuthorizeAccount::authorize(AuthorizationEnum::HOLDING_VIEW);

        return new HoldingResource($holding->load(['tenant']));
    }

    /**
     * Remove the specified holding.
     * @throws AuthorizationException|Throwable
     */
    public function destroy(Holding $holding): JsonResponse
    {
        AuthorizeAccount::authorize(AuthorizationEnum::HOLDING_VIEW);

        if (!$holding->canBeDeleted()) {
            return response()->json([
                'message' => 'Não é possível excluir um holding que possui empresas associadas',
            ], Response::HTTP_CONFLICT);
        }

        DB::transaction(static function () use ($holding): void {
            $holding->delete();
        });

        return response()->json([
            'message' => 'Holding excluído com sucesso',
        ]);
    }

    /**
     * Upload logo for the holding.
     * @throws AuthorizationException
     */
    public function uploadLogo(UploadLogoRequest $request, Holding $holding): JsonResponse
    {
        AuthorizeAccount::authorize(AuthorizationEnum::HOLDING_VIEW);

        $file = $request->file('logo');

        if ($holding->logo_path && Storage::disk('s3')->exists($holding->logo_path)) {
            Storage::disk('s3')->delete($holding->logo_path);
        }

        if ($file) {
            $path = $file->store("holdings/{$holding->id}/logo", 's3');
        } else {
            return response()->json([
                'message' => 'Arquivo de logo não encontrado',
            ], Response::HTTP_BAD_REQUEST);
        }

        $holding->update(['logo_path' => $path]);

        cache()->forget("holding:{$holding->id}:logo_url");

        $freshHolding = $holding->fresh();

        return response()->json([
            'message'  => 'Logo enviado com sucesso',
            'logo_url' => $freshHolding ? $freshHolding->logo_url : null,
        ]);
    }

    /**
     * Store a newly created holding.
     * @throws Throwable
     */
    public function store(CreateHoldingRequest $request): HoldingResource
    {
        $data = $request->validated();

        if (isset($data['tax_id'])) {
            $data['tax_id'] = $this->convertToSanitizedString($data['tax_id']);
        }

        $data['tenant_id'] = auth()->user()?->tenant_id;

        $holding = DB::transaction(static fn () => Holding::query()->create($data));

        return new HoldingResource($holding->load(['tenant']));
    }

    /**
     * Convert a mixed value to a sanitized string for CNPJ.
     * @throws JsonException
     */
    private function convertToSanitizedString(mixed $value): string
    {
        return CnpjHelper::sanitize(toString($value));
    }

    /**
     * Update the specified holding.
     * @throws Throwable
     */
    public function update(UpdateHoldingRequest $request, Holding $holding): HoldingResource
    {
        AuthorizeAccount::authorize(AuthorizationEnum::HOLDING_VIEW);

        $data = $request->validated();

        if (isset($data['tax_id'])) {
            $data['tax_id'] = $this->convertToSanitizedString($data['tax_id']);
        }

        DB::transaction(static function () use ($holding, $data): void {
            $holding->update($data);
        });

        $freshHolding = $holding->fresh();

        if (!$freshHolding) {
            $freshHolding = $holding;
        }

        return new HoldingResource($freshHolding->load(['tenant']));
    }

    /**
     * Remove logo from the holding.
     */
    public function removeLogo(Holding $holding): JsonResponse
    {

        if ($holding->logo_path && Storage::disk('s3')->exists($holding->logo_path)) {
            Storage::disk('s3')->delete($holding->logo_path);
        }

        $holding->update(['logo_path' => null]);

        cache()->forget("holding:{$holding->id}:logo_url");

        return response()->json([
            'message' => 'Logo removido com sucesso',
        ]);
    }

    /**
     * Validate CNPJ.
     * @throws JsonException
     */
    public function validateCnpj(Request $request): JsonResponse
    {
        $request->validate([
            'tax_id'     => ['required', 'string'],
            'holding_id' => ['nullable', 'string', 'exists:holdings,id'],
        ]);

        $input  = $request->input('tax_id');
        $tax_id = CnpjHelper::sanitize(toString($input));

        if (!CnpjHelper::isValid($tax_id)) {
            return response()->json([
                'valid'   => false,
                'message' => 'CNPJ inválido',
            ]);
        }

        $query = Holding::query()->where('tax_id', $tax_id)
            ->where('tenant_id', auth()->user()?->tenant_id);

        if ($request->filled('holding_id')) {
            $query->where('id', '!=', $request->input('holding_id'));
        }

        $exists = $query->exists();

        return response()->json([
            'valid'     => !$exists,
            'message'   => $exists ? 'CNPJ já cadastrado' : 'CNPJ válido',
            'formatted' => new Holding(['tax_id' => $tax_id])->formatted_cnpj,
        ]);
    }
}
