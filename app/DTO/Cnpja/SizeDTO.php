<?php declare(strict_types = 1);

namespace App\DTO\Cnpja;

readonly class SizeDTO
{
    public function __construct(
        public ?int $id,
        public ?string $acronym,
        public ?string $text
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            acronym: $data['acronym'] ?? null,
            text: $data['text'] ?? null,
        );
    }
}
