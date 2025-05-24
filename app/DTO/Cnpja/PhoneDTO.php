<?php declare(strict_types = 1);

namespace App\DTO\Cnpja;

readonly class PhoneDTO
{
    public function __construct(
        public ?string $area,
        public ?string $number
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            area: $data['area'] ?? null,
            number: $data['number'] ?? null,
        );
    }
}
