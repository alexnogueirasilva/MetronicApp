<?php declare(strict_types = 1);

namespace App\Http\Resources\Finance;

use App\Models\Finance\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @mixin Account
 */
class AccountResource extends JsonResource
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
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'type'        => $this->type,
            'currency'    => $this->currency,
            'balance'     => $this->balance,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'tenant_id'   => $this->tenant_id,
            'created_by'  => $this->created_by,
        ];
    }
}
