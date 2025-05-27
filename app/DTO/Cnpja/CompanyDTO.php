<?php declare(strict_types = 1);

namespace App\DTO\Cnpja;

readonly class CompanyDTO
{
    public function __construct(
        public ?int      $id,
        public ?string   $name,
        public ?int      $equity,
        public NatureDTO $nature,
        public SizeDTO   $size,
        public array     $members,
        public ?string    $entityType,
        public ?string    $entityId,
    ) {}

    public static function fromArray(array $data, ?string $entityType, ?string $entityId): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? null,
            equity: $data['equity'] ?? null,
            nature: NatureDTO::fromArray($data['nature'] ?? []),
            size: SizeDTO::fromArray($data['size'] ?? []),
            members: array_map(static fn ($member): MemberDTO => MemberDTO::fromArray($member), $data['members'] ?? []),
            entityType: $entityType ?? null,
            entityId: $entityId ?? null,
        );
    }

}
