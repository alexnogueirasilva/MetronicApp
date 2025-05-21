<?php declare(strict_types = 1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\{actingAs, assertDatabaseHas, assertDatabaseMissing, deleteJson, getJson, postJson, putJson};

uses(RefreshDatabase::class);

beforeEach(function (): void {
    global $user;
    $user = User::factory()->create();
    actingAs($user);
});

it('should be able to list users', function (): void {
    User::factory()->count(3)->create();

    $response = getJson(route('users.index'), [
        'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'nickname',
                    'email',
                    'avatar',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

it('should be able to filter users by name', function (): void {
    User::factory()->create([
        'nickname' => 'Test User',
    ]);

    $response = getJson(route('users.index', [
        'filter' => [
            'nickname' => 'Test',
        ],
    ]), [
        'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'nickname',
                    'email',
                    'avatar',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    $responseData = $response->json('data');
    $found        = false;

    foreach ($responseData as $user) {
        if ($user['nickname'] === 'Test User') {
            $found = true;

            break;
        }
    }
    expect($found)->toBeTrue();
});

it('should be able to create a new user', function (): void {
    $userData = [
        'nickname' => 'New User',
        'email'    => 'newuser@example.com',
        'password' => 'password123',
    ];

    $response = postJson(route('users.store'), $userData, [
        'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'nickname',
                'email',
                'avatar',
                'created_at',
                'updated_at',
            ],
        ]);

    assertDatabaseHas('users', [
        'nickname' => 'New User',
        'email'    => 'newuser@example.com',
    ]);
});

it('should be able to show a user', function (): void {
    $user = User::factory()->create();

    $response = getJson(route('users.show', $user->id), [
        'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'nickname',
                'email',
                'avatar',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
        ]);

    expect($response->json('data.id'))->toBe($user->id)
        ->and($response->json('data.nickname'))->toBe($user->nickname)
        ->and($response->json('data.email'))->toBe($user->email);
});

it('should be able to update a user', function (): void {
    $user       = User::factory()->create();
    $updateData = [
        'nickname' => 'Updated Name',
        'email'    => 'updated@example.com',
    ];

    $response = putJson(route('users.update', $user->id), $updateData, [
        'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'nickname',
                'email',
                'avatar',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
        ]);

    expect($response->json('data.nickname'))->toBe('Updated Name')
        ->and($response->json('data.email'))->toBe('updated@example.com');

    assertDatabaseHas('users', [
        'id'       => $user->id,
        'nickname' => 'Updated Name',
        'email'    => 'updated@example.com',
    ]);
});

it('should be able to delete a user', function (): void {
    $user = User::factory()->create();

    $response = deleteJson(route('users.destroy', $user->id), [], [
        'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $response->assertNoContent();

    assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});
