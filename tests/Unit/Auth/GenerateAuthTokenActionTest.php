<?php declare(strict_types = 1);

use App\Actions\Auth\GenerateAuthTokenAction;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function (): void {
    $this->action = new GenerateAuthTokenAction();
});

it('generates a Sanctum token for a user', function (): void {
    $user = User::factory()->create();

    $token = $this->action->execute($user);

    // Verify token is a string
    expect($token)->toBeString();

    // Verify token exists in the database
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_type' => User::class,
        'tokenable_id'   => $user->id,
    ]);

    // Verify token name contains the default name
    $dbToken = PersonalAccessToken::where('tokenable_id', $user->id)
        ->where('tokenable_type', User::class)
        ->first();

    expect($dbToken->name)->toContain('api-token');
});

it('allows custom token names', function (): void {
    $user       = User::factory()->create();
    $customName = 'custom-test-token';

    $token = $this->action->execute($user, $customName);

    // Verify token exists with custom name
    $dbToken = PersonalAccessToken::where('tokenable_id', $user->id)
        ->where('tokenable_type', User::class)
        ->first();

    expect($dbToken->name)->toContain($customName);
});

it('appends timestamp to token name for uniqueness', function (): void {
    $user = User::factory()->create();

    $token = $this->action->execute($user);

    // Get the token from the database
    $dbToken = PersonalAccessToken::where('tokenable_id', $user->id)
        ->where('tokenable_type', User::class)
        ->first();

    // Token name should contain a timestamp in format "token-name-timestamp"
    $timestamp = (int)explode('-', $dbToken->name)[2] ?? null;

    // Verify the timestamp is recent (within the last minute)
    expect($timestamp)->toBeInt()
        ->and($timestamp)->toBeLessThanOrEqual(now()->timestamp)
        ->and($timestamp)->toBeGreaterThan(now()->subMinute()->timestamp);
});
