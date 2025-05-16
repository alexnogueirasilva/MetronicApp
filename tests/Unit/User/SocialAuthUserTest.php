<?php declare(strict_types = 1);
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates new user when not found', function () {
    // Dados de teste
    $provider   = 'google';
    $providerId = '12345';
    $userData   = [
        'name'   => 'Test User',
        'email'  => 'test@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ];

    // Executar método
    $user = User::findOrCreateSocialUser($provider, $providerId, $userData);

    // Verificações
    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->avatar)->toEqual('https://example.com/avatar.jpg');
    expect($user->provider)->toEqual('google');
    expect($user->provider_id)->toEqual('12345');
    expect($user->email_verified_at)->not->toBeNull();
});
it('returns existing user when found by provider', function () {
    // Criar usuário existente
    $existingUser = User::create([
        'name'        => 'Existing User',
        'email'       => 'existing@example.com',
        'provider'    => 'google',
        'provider_id' => '12345',
    ]);

    // Dados de teste (mesmo provider e provider_id)
    $provider   = 'google';
    $providerId = '12345';
    $userData   = [
        'name'   => 'Updated User',
        'email'  => 'updated@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ];

    // Executar método
    $user = User::findOrCreateSocialUser($provider, $providerId, $userData);

    // Verificar que retornou o usuário existente (mesmo ID)
    expect($user->id)->toEqual($existingUser->id);

    // Verificar que os dados NÃO foram atualizados
    expect($user->name)->toEqual('Existing User');
    expect($user->email)->toEqual('existing@example.com');
});
it('links existing account when found by email', function () {
    // Criar usuário existente com email
    $existingUser = User::create([
        'name'        => 'Email User',
        'email'       => 'test@example.com',
        'password'    => bcrypt('password'),
        'provider'    => null,
        'provider_id' => null,
    ]);

    // Dados de teste (mesmo email)
    $provider   = 'google';
    $providerId = '12345';
    $userData   = [
        'name'   => 'Test User',
        'email'  => 'test@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ];

    // Executar método
    $user = User::findOrCreateSocialUser($provider, $providerId, $userData);

    // Verificar que retornou o usuário existente (mesmo ID)
    expect($user->id)->toEqual($existingUser->id);

    // Verificar que os dados de provider foram atualizados
    expect($user->provider)->toEqual('google');
    expect($user->provider_id)->toEqual('12345');

    // Verificar que o avatar foi atualizado
    expect($user->avatar)->toEqual('https://example.com/avatar.jpg');

    // Verificar que nome e email não mudaram
    expect($user->name)->toEqual('Email User');
    expect($user->email)->toEqual('test@example.com');
});
it('handles missing avatar gracefully', function () {
    // Dados de teste sem avatar
    $provider   = 'google';
    $providerId = '12345';
    $userData   = [
        'name'  => 'Test User',
        'email' => 'test@example.com',
        // Sem avatar
    ];

    // Executar método
    $user = User::findOrCreateSocialUser($provider, $providerId, $userData);

    // Verificar que usuário foi criado
    expect($user)->toBeInstanceOf(User::class);
    expect($user->avatar)->toBeNull();
});
