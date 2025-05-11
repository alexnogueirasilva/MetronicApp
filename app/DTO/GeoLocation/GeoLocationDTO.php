<?php
declare(strict_types = 1);

namespace App\DTO\GeoLocation;

readonly class GeoLocationDTO
{
    public function __construct(
        public string $ip,
        public string $city,
        public string $country,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            ip: is_string($data['query'] ?? null) ? $data['query'] : 'Desconhecido',
            city: is_string($data['city'] ?? null) ? $data['city'] : 'Desconhecido',
            country: is_string($data['country'] ?? null) ? $data['country'] : 'Desconhecido',
        );
    }
}
