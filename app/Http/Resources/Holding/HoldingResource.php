<?php declare(strict_types = 1);

namespace App\Http\Resources\Holding;

use App\Models\Holding;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Holding
 */
class HoldingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'legal_name'     => $this->legal_name,
            'cnpj'           => $this->tax_id,
            'formatted_cnpj' => $this->formatted_cnpj,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'description'    => $this->description,
            'logo_url'       => $this->logo_url,
            'settings'       => $this->settings,
            'address'        => $this->address,
            'full_address'   => $this->full_address,
            'is_active'      => $this->is_active,
            'can_be_deleted' => $this->canBeDeleted(),
            'created_at'     => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at'     => $this->updated_at->format('Y-m-d H:i:s'),
            'tenant'         => $this->whenLoaded('tenant', fn (): array => [
                'id'   => $this->tenant->id,
                'name' => $this->tenant->name,
            ]),
            'companies_count' => $this->whenCounted('companies'),
        ];
    }
}
