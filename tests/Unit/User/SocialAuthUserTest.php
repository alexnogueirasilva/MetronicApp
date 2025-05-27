<?php
declare(strict_types = 1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates new user when not found', function () {
    $provider   = 'google';
    $providerId = '12345';
    $userData   = [
        'nickname' => 'Test User',
        'email'    => 'test@example.com',
        'avatar'   => 'https://example.com/avatar.jpg',
    ];

    $user = User::findOrCreateSocialUser($provider, $providerId, $userData);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->nickname)->toEqual('Test User')
        ->and($user->email)->toEqual('test@example.com')
        ->and($user->avatar)->toEqual('https://example.com/avatar.jpg')
        ->and($user->provider)->toEqual('google')
        ->and($user->provider_id)->toEqual('12345')
        ->and($user->email_verified_at)->not->toBeNull();
});
it('returns existing user when found by provider', function () {
    $existingUser = User::create([
        'nickname'    => 'Existing User',
        'email'       => 'existing@example.com',
        'provider'    => 'google',
        'provider_id' => '12345',
    ]);

    $provider   = 'google';
    $providerId = '12345';
    $userData   = [
        'nickname' => 'Updated User',
        'email'    => 'updated@example.com',
        'avatar'   => 'https://example.com/avatar.jpg',
    ];

    $user = User::findOrCreateSocialUser($provider, $providerId, $userData);

    expect($user->id)->toEqual($existingUser->id)
        ->and($user->nickname)->toEqual('Existing User')
        ->and($user->email)->toEqual('existing@example.com');

});

it('links existing account when found by email', function () {
    $existingUser = User::create([
        'nickname'    => 'Email User',
        'email'       => 'test@example.com',
        'password'    => bcrypt('password'),
        'provider'    => null,
        'provider_id' => null,
    ]);

    $provider   = 'google';
    $providerId = '12345';
    $userData   = [
        'name'   => 'Test User',
        'email'  => 'test@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ];

    $user = User::findOrCreateSocialUser($provider, $providerId, $userData);

    expect($user->id)->toEqual($existingUser->id)
        ->and($user->provider)->toEqual('google')
        ->and($user->provider_id)->toEqual('12345')
        ->and($user->avatar)->toEqual('https://example.com/avatar.jpg')
        ->and($user->nickname)->toEqual('Email User')
        ->and($user->email)->toEqual('test@example.com');

});

it('handles missing avatar gracefully', function () {
    $provider   = 'google';
    $providerId = '12345';
    $userData   = [
        'nickname' => 'Test User',
        'email'    => 'test@example.com',
        // Sem avatar
    ];

    $user = User::findOrCreateSocialUser($provider, $providerId, $userData);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->avatar)->toBeNull();
});
