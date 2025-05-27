<?php
declare(strict_types = 1);

namespace App\DTO\Finance;

use Illuminate\Support\Carbon;
use JsonException;

readonly class AccountDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $type,
        public string $currency,
        public string $balance,
        public Carbon $created_at,
        public Carbon $updated_at,
        public string $tenant_id,
        public string $created_by,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws JsonException
     */
    public function formArray(array $data): self
    {
        return new self(
            id: toString($data['id']),
            name: toString($data['name']),
            description: toString($data['description']),
            type: toString($data['type']),
            currency: toString($data['currency']),
            balance: toString($data['balance']),
            created_at: Carbon::parse(toString($data['created_at'])),
            updated_at: Carbon::parse(toString($data['updated_at'])),
            tenant_id: toString($data['tenant_id']),
            created_by: toString($data['created_by']),
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'type'        => $this->type,
            'currency'    => $this->currency,
            'balance'     => $this->balance,
            'created_at'  => $this->created_at->toDateTimeString(),
            'updated_at'  => $this->updated_at->toDateTimeString(),
            'tenant_id'   => $this->tenant_id,
            'created_by'  => $this->created_by,
        ];
    }
}
