<?php declare(strict_types = 1);

namespace App\DTO\Auth;

use JsonException;

readonly class TotpSecretDTO
{
    public function __construct(
        public string $secret,
        public string $qr,
    ) {}

    /**
     * @param  array<string, string>  $data
     *
     * @throws JsonException
     */
    public function fromArray(array $data): self
    {
        return new self(
            secret: toString($data['secret']),
            qr: toString($data['qr']),
        );
    }
}
