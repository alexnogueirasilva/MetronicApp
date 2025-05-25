<?php
declare(strict_types = 1);

namespace App\DTO\ACL;

/**
 * @phpstan-type PermissionsArray array<int, int|string>
 */
readonly class CreateRoleData
{
    /**
     * @param  PermissionsArray|null  $permissions
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $icon = null,
        public ?array $permissions = null,
    ) {}
}
