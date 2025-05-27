<?php declare(strict_types = 1);

namespace App\DTO\Cnpja;

readonly class CnpjDataDTO
{
    public function __construct(
        public ?string $updated,
        public ?string $taxId,
        public CompanyDTO $company,
        public ?string $alias,
        public ?string $founded,
        public ?bool $head,
        public ?string $statusDate,
        public StatusDTO $status,
        public AddressDTO $address,
        public array $phones,
        public array $emails,
        public ActivityDTO $mainActivity,
        public array $sideActivities,
        public array $registrations,
        public ?string $entityType,
        public ?string $entityId
    ) {}
    public static function fromArray(array $response, ?string $entityType = null, ?string $entityId = null): self
    {
        return new self(
            updated: $response['updated'] ?? null,
            taxId: $response['taxId'] ?? null,
            company: CompanyDTO::fromArray($response['company'] ?? [], $entityType, $entityId),
            alias: $response['alias'] ?? null,
            founded: $response['founded'] ?? null,
            head: $response['head'] ?? null,
            statusDate: $response['statusDate'] ?? null,
            status: StatusDTO::fromArray($response['status'] ?? []),
            address: AddressDTO::fromArray($response['address'] ?? []),
            phones: array_map(static fn ($phone): PhoneDTO => PhoneDTO::fromArray($phone), $response['phones'] ?? []),
            emails: array_map(static fn ($email): EmailDTO => EmailDTO::fromArray($email), $response['emails'] ?? []),
            mainActivity: ActivityDTO::fromArray($response['mainActivity'] ?? []),
            sideActivities: array_map(static fn ($activity): ActivityDTO => ActivityDTO::fromArray($activity), $response['sideActivities'] ?? []),
            registrations: array_map(static fn ($registration): RegistrationDTO => RegistrationDTO::fromArray($registration), $response['registrations'] ?? []),
            entityType: $entityType,
            entityId: $entityId,
        );
    }
}
