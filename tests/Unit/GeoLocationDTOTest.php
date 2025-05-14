<?php declare(strict_types = 1);

use App\DTO\GeoLocation\GeoLocationDTO;

test('GeoLocationDTO: instancia e fromApi', function (): void {
    $dto = new GeoLocationDTO('8.8.8.8', 'SP', 'BR');
    expect($dto->ip)->toBe('8.8.8.8')
        ->and($dto->city)->toBe('SP')
        ->and($dto->country)->toBe('BR');

    $api = GeoLocationDTO::fromApi(['query' => '1.1.1.1', 'city' => 'Curitiba', 'country' => 'BR']);
    expect($api->ip)->toBe('1.1.1.1')
        ->and($api->city)->toBe('Curitiba')
        ->and($api->country)->toBe('BR');

    $miss = GeoLocationDTO::fromApi([]);
    expect($miss->ip)->toBe('Desconhecido')
        ->and($miss->city)->toBe('Desconhecido')
        ->and($miss->country)->toBe('Desconhecido');

    $invalid = GeoLocationDTO::fromApi(['query' => [], 'city' => 1, 'country' => null]);
    expect($invalid->ip)->toBe('Desconhecido')
        ->and($invalid->city)->toBe('Desconhecido')
        ->and($invalid->country)->toBe('Desconhecido');
});
