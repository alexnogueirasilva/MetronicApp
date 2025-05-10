<?php declare(strict_types = 1);

namespace App\DTO\Auth;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public string $device,
    ) {}

    /**
     * @param array{email: string, password: string, device?: string} $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            email: (string) $data['email'],
            password: (string) $data['password'],
            device: (string) ($data['device'] ?? 'web'),
        );
    }
}
