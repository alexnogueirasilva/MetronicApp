<?php declare(strict_types = 1);

namespace App\Actions\GeoLocationAction;

use App\DTO\GeoLocation\GeoLocationDTO;
use Illuminate\Support\Facades\Http;

class GeoLocationAction
{
    public function lookup(string $ip): GeoLocationDTO
    {
        $response = Http::timeout(5)->get("http://ip-api.com/json/{$ip}");

        if (!$response->ok()) {
            return new GeoLocationDTO($ip, 'Desconhecido', 'Desconhecido');
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return GeoLocationDTO::fromApi($data);
    }
}
