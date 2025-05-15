<?php declare(strict_types = 1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\{actingAs, deleteJson, getJson, postJson, putJson};

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
                    'name',
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
        'name' => 'Test User',
    ]);

    $response = getJson(route('users.index', [
        'filter' => [
            'name' => 'Test',
        ],
    ]), [
        'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
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
        if ($user['name'] === 'Test User') {
            $found = true;

            break;
        }
    }
    expect($found)->toBeTrue();
});

it('should be able to create a new user', function (): void {
    $userData = [
        'name'     => 'New User',
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
                'name',
                'email',
                'avatar',
                'created_at',
                'updated_at',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'name'  => 'New User',
        'email' => 'newuser@example.com',
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
                'name',
                'email',
                'avatar',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
        ]);

    expect($response->json('data.id'))->toBe($user->id)
        ->and($response->json('data.name'))->toBe($user->name)
        ->and($response->json('data.email'))->toBe($user->email);
});

it('should be able to update a user', function (): void {
    $user       = User::factory()->create();
    $updateData = [
        'name'  => 'Updated Name',
        'email' => 'updated@example.com',
    ];

    $response = putJson(route('users.update', $user->id), $updateData, [
        'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'avatar',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
        ]);

    expect($response->json('data.name'))->toBe('Updated Name')
        ->and($response->json('data.email'))->toBe('updated@example.com');

    $this->assertDatabaseHas('users', [
        'id'    => $user->id,
        'name'  => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

it('should be able to delete a user', function (): void {
    $user = User::factory()->create();

    $response = deleteJson(route('users.destroy', $user->id), [], [
        'Idempotency-Key' => \Illuminate\Support\Str::uuid()->toString(),
    ]);

    $response->assertNoContent();

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});
