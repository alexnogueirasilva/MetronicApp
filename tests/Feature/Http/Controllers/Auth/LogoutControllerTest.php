<?php declare(strict_types = 1);

use App\Models\User;
use Laravel\Sanctum\{PersonalAccessToken, TransientToken};

use function Pest\Laravel\{actingAs, deleteJson};

it('logs out the user and deletes the current token', function (): void {
    $user = User::factory()->create();

    $token = $user->createToken('test-device');

    deleteJson(route('auth.logout'), [], [
        'Authorization' => 'Bearer ' . $token->plainTextToken,
    ])
        ->assertOk()
        ->assertJson([
            'message' => 'Logged out successfully.',
        ]);

    // Agora funciona, pois esse é o token real usado
    expect(PersonalAccessToken::find($token->accessToken->id))->toBeNull();
});

it('does not call delete if token is a transient token', function (): void {
    $user = User::factory()->create();

    $mockToken = Mockery::mock(TransientToken::class);

    $user->setRelation('currentAccessToken', $mockToken);

    actingAs($user);

    // Roda o logout
    deleteJson(route('auth.logout'))
        ->assertOk()
        ->assertJson(['message' => 'Logged out successfully.']);
});
