<?php declare(strict_types = 1);

namespace App\DTO\Cnpja;

readonly class RegistrationDTO
{
    public function __construct(
        public ?string $state,
        public ?string $number,
        public ?bool $enabled,
        public ?string $statusDate,
        public StatusDTO $status,
        public RegistrationTypeDTO $type
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            state: $data['state'] ?? null,
            number: $data['number'] ?? null,
            enabled: $data['enabled'] ?? null,
            statusDate: $data['statusDate'] ?? null,
            status: StatusDTO::fromArray($data['status'] ?? []),
            type: RegistrationTypeDTO::fromArray($data['type'] ?? []),
        );
    }
}
