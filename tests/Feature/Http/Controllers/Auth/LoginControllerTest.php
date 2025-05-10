<?php declare(strict_types = 1);

use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $user = User::factory()->create([
        'name'     => 'Reggie Swift',
        'email'    => 'test@example.com',
        'password' => bcrypt('secret123'),
    ]);
});

it('logs in successfully with correct credentials', function (): void {
    $response = postJson(route('auth.login'), [
        'email'    => 'test@example.com',
        'password' => 'secret123',
        'device'   => 'TestDevice',
    ]);

    $response
        ->assertOk()
        ->assertJson(
            fn (AssertableJson $json): AssertableJson => $json->hasAll(['token', 'user'])
            ->has('user.id')
            ->where('user.email', 'test@example.com')
            ->where('user.name', 'Reggie Swift')
        );

    expect(auth()->user())->toBeNull();
});

it('returns unauthorized for invalid credentials', function (): void {
    $response = postJson(route('auth.login'), [
        'email'    => 'test@example.com',
        'password' => 'wrong-password',
        'device'   => 'TestDevice',
    ]);

    $response
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'The provided credentials are incorrect.',
        ]);
});
