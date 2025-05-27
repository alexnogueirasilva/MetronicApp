<?php declare(strict_types = 1);

namespace App\DTO\Cnpja;

readonly class MemberDTO
{
    public function __construct(
        public ?string $since,
        public RoleDTO $role,
        public PersonDTO $person
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            since: $data['since'] ?? null,
            role: RoleDTO::fromArray($data['role'] ?? []),
            person: PersonDTO::fromArray($data['person'] ?? []),
        );
    }
}
