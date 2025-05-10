<?php declare(strict_types = 1);

use App\Models\Auth\Role;
use App\Models\User;

use function Pest\Laravel\{actingAs, getJson};

beforeEach(function (): void {
    global $user;

    $user = User::factory()->create();
    actingAs($user);

    Role::factory(50)->create();
});

it('should be abel to list roles', function (): void {
    $response = getJson(route('acl.role'));

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

it('should be able filter role', function (): void {
    $role = Role::factory()->create([
        'name' => 'test-role',
    ]);

    $response = getJson(route('acl.role', [
        'filter' => [
            'name' => 'test-role',
        ],
    ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
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
        ])
        ->assertJsonFragment([
            'id' => $role->id,
        ]);
});
