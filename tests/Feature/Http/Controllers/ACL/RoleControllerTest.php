<?php declare(strict_types = 1);

use App\Models\Auth\{Permission, Role};
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\{actingAs, assertDatabaseHas, getJson, postJson};

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

it('should create a role and sync permissions', function (): void {
    $permissions = Permission::factory()->count(3)->create();

    $payload = [
        'name'        => 'manager',
        'description' => 'Manages things',
        'icon'        => 'manager-icon',
        'is_default'  => true,
        'permissions' => $permissions->pluck('id')->toArray(),
    ];

    $response = postJson(
        route('acl.role.store'),
        $payload,
        ['Idempotency-Key' => Str::uuid()->toString()]
    );

    $response->assertCreated()
        ->assertJson(
            fn (AssertableJson $json) => $json->has(
                'data',
                fn ($json) => $json
            ->where('name', 'manager')
            ->where('description', 'Manages things')
            ->etc()
            )
        );

    $role = Role::where('name', 'manager')->firstOrFail();

    expect($role->permissions)->toHaveCount(3);

    foreach ($permissions as $permission) {
        assertDatabaseHas('permission_role', [
            'role_id'       => $role->id,
            'permission_id' => $permission->id,
        ]);
    }
});
