<?php declare(strict_types = 1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\{GoogleProvider, User as SocialiteUser};

use function Pest\Laravel\{assertDatabaseCount, assertDatabaseHas, getJson, postJson};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mock = Mockery::mock(GoogleProvider::class);
    Socialite::shouldReceive('driver')->with('google')->andReturn($this->mock);
});

it('returns oauth url for api flow', function () {
    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('redirect')->andReturn($this->mock);
    $this->mock->shouldReceive('getTargetUrl')->andReturn('https://accounts.google.com/o/oauth2/auth?test=1');

    $response = getJson('/v1/auth/social/google');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => ['url'],
            'meta' => ['api_version'],
        ])
        ->assertJson([
            'status' => 'success',
            'data'   => [
                'url' => 'https://accounts.google.com/o/oauth2/auth?test=1',
            ],
        ]);
});

it('handles invalid provider in api flow', function () {
    $response = getJson('/v1/auth/social/invalid-provider');

    $response->assertStatus(400)
        ->assertJson([
            'status'  => 'error',
            'message' => 'Provedor de autenticação não suportado.',
        ]);
});

it('creates new user with social login', function () {
    $socialiteUser = new SocialiteUser();
    $socialiteUser->map([
        'id'       => '123456789',
        'nickname' => 'Test User',
        'email'    => 'testuser@gmail.com',
        'avatar'   => 'https://example.com/avatar.jpg',
    ]);

    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('user')->andReturn($socialiteUser);

    $response = postJson(route('auth.social.callback', ['provider' => 'google']), [
        'code' => 'valid-auth-code',
    ]);

    assertDatabaseHas('users', [
        'email'       => 'testuser@gmail.com',
        'nickname'    => 'Test User',
        'provider'    => 'google',
        'provider_id' => '123456789',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user',
                'token',
                'requires_otp',
            ],
        ]);
});

it('connects existing account by email', function () {
    $existingUser = User::create([
        'nickname' => 'Existing User',
        'email'    => 'testuser@gmail.com',
        'password' => bcrypt('password'),
    ]);

    $socialiteUser = new SocialiteUser();
    $socialiteUser->map([
        'id'       => '123456789',
        'nickname' => 'Test User',
        'email'    => 'testuser@gmail.com',
        'avatar'   => 'https://example.com/avatar.jpg',
    ]);

    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('user')->andReturn($socialiteUser);

    $response = postJson(route('auth.social.callback', ['provider' => 'google']), [
        'code' => 'valid-auth-code',
    ]);

    assertDatabaseCount('users', 1);
    assertDatabaseHas('users', [
        'id'          => $existingUser->id,
        'email'       => 'testuser@gmail.com',
        'provider'    => 'google',
        'provider_id' => '123456789',
    ]);

    $response->assertStatus(200);
});

it('reuses existing social user', function () {
    $existingUser = User::create([
        'nickname'    => 'Social User',
        'email'       => 'socialuser@gmail.com',
        'provider'    => 'google',
        'provider_id' => '123456789',
    ]);

    $socialiteUser = new SocialiteUser();
    $socialiteUser->map([
        'id'     => '123456789',
        'name'   => 'Social User Updated',
        'email'  => 'socialuser@gmail.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('user')->andReturn($socialiteUser);

    $response = postJson(route('auth.social.callback', ['provider' => 'google']), [
        'code' => 'valid-auth-code',
    ]);

    assertDatabaseCount('users', 1);

    $response->assertStatus(200);
});
it('handles errors in social callback', function () {
    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('user')->andThrow(new Exception('Invalid credentials'));

    $response = postJson(route('auth.social.callback', ['provider' => 'google']), [
        'code' => 'invalid-code',
    ]);

    $response->assertStatus(500)
        ->assertJson([
            'status'  => 'error',
            'message' => 'Falha na autenticação social. Invalid credentials',
        ]);
});
