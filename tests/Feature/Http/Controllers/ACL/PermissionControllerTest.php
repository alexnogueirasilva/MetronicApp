<?php declare(strict_types = 1);

use App\Models\Auth\Permission;
use App\Models\User;

use function Pest\Laravel\{actingAs, getJson};

beforeEach(function (): void {
    global $user;
    $user = User::factory()->create();
    actingAs($user);
});

it('should be able to list permissions', function (): void {
    $response = getJson(route('acl.permission'));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

it('should be able to filter permission', function (): void {
    Permission::factory()->create([
        'name' => 'test-permission',
    ]);

    $response = getJson(route('acl.permission', [
        'filter' => [
            'name' => 'test-permission',
        ],
    ]));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});
