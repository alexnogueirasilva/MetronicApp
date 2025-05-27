<?php declare(strict_types = 1);

namespace App\DTO\Cnpja;

readonly class ActivityDTO
{
    public function __construct(
        public ?int $id,
        public ?string $text
    ) {}

    /**
     * @param array{id: int, text: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            text: $data['text'] ?? null,
        );
    }
}
