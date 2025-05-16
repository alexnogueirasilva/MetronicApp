<?php declare(strict_types = 1);
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\{GoogleProvider, User as SocialiteUser};

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Configuração para mock do Socialite
    $this->mock = Mockery::mock(GoogleProvider::class);
    Socialite::shouldReceive('driver')->with('google')->andReturn($this->mock);
});
it('returns oauth url for api flow', function () {
    // Mock do método stateless() e redirect()
    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('redirect')->andReturn($this->mock);
    $this->mock->shouldReceive('getTargetUrl')->andReturn('https://accounts.google.com/o/oauth2/auth?test=1');

    // Fazer requisição para obter URL de autenticação
    $response = $this->getJson('/api/v1/auth/social/google');

    // Verificar resposta
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
    // Tentar com provedor inválido
    $response = $this->getJson('/api/v1/auth/social/invalid-provider');

    // Verificar erro
    $response->assertStatus(400)
        ->assertJson([
            'status'  => 'error',
            'message' => 'Provedor de autenticação não suportado.',
        ]);
});
it('creates new user with social login', function () {
    // Criar usuário socialite mock
    $socialiteUser = new SocialiteUser();
    $socialiteUser->map([
        'id'     => '123456789',
        'name'   => 'Test User',
        'email'  => 'testuser@gmail.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    // Mock do Socialite para retornar nosso usuário mock
    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('user')->andReturn($socialiteUser);

    // Fazer requisição de callback
    $response = $this->postJson('/api/v1/auth/social/google/callback', [
        'code' => 'valid-auth-code',
    ]);

    // Verificar se usuário foi criado
    $this->assertDatabaseHas('users', [
        'email'       => 'testuser@gmail.com',
        'name'        => 'Test User',
        'provider'    => 'google',
        'provider_id' => '123456789',
    ]);

    // Verificar resposta
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
    // Criar usuário existente com mesmo email
    $existingUser = User::create([
        'name'     => 'Existing User',
        'email'    => 'testuser@gmail.com',
        'password' => bcrypt('password'),
    ]);

    // Criar usuário socialite mock
    $socialiteUser = new SocialiteUser();
    $socialiteUser->map([
        'id'     => '123456789',
        'name'   => 'Test User',
        'email'  => 'testuser@gmail.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    // Mock do Socialite para retornar nosso usuário mock
    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('user')->andReturn($socialiteUser);

    // Fazer requisição de callback
    $response = $this->postJson('/api/v1/auth/social/google/callback', [
        'code' => 'valid-auth-code',
    ]);

    // Verificar se o usuário existente foi atualizado em vez de criar um novo
    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseHas('users', [
        'id'          => $existingUser->id,
        'email'       => 'testuser@gmail.com',
        'provider'    => 'google',
        'provider_id' => '123456789',
    ]);

    // Verificar resposta
    $response->assertStatus(200);
});
it('reuses existing social user', function () {
    // Criar usuário social existente
    $existingUser = User::create([
        'name'        => 'Social User',
        'email'       => 'socialuser@gmail.com',
        'provider'    => 'google',
        'provider_id' => '123456789',
    ]);

    // Criar usuário socialite mock
    $socialiteUser = new SocialiteUser();
    $socialiteUser->map([
        'id'     => '123456789',
        'name'   => 'Social User Updated',
        'email'  => 'socialuser@gmail.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    // Mock do Socialite para retornar nosso usuário mock
    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('user')->andReturn($socialiteUser);

    // Fazer requisição de callback
    $response = $this->postJson('/api/v1/auth/social/google/callback', [
        'code' => 'valid-auth-code',
    ]);

    // Verificar se nenhum novo usuário foi criado
    $this->assertDatabaseCount('users', 1);

    // Verificar resposta
    $response->assertStatus(200);
});
it('handles errors in social callback', function () {
    // Mock para simular um erro
    $this->mock->shouldReceive('stateless')->andReturnSelf();
    $this->mock->shouldReceive('user')->andThrow(new \Exception('Invalid credentials'));

    // Fazer requisição de callback
    $response = $this->postJson('/api/v1/auth/social/google/callback', [
        'code' => 'invalid-code',
    ]);

    // Verificar resposta de erro
    $response->assertStatus(500)
        ->assertJson([
            'status'  => 'error',
            'message' => 'Falha na autenticação social. Invalid credentials',
        ]);
});
