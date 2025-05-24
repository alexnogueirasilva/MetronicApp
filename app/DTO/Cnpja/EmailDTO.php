<?php declare(strict_types = 1);

namespace App\DTO\Cnpja;

readonly class EmailDTO
{
    public function __construct(
        public ?string $address,
        public ?string $domain
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            address: $data['address'] ?? null,
            domain: $data['domain'] ?? null,
        );
    }
}
