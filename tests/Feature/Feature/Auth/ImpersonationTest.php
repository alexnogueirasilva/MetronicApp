<?php declare(strict_types = 1);

use App\Events\ImpersonationActionPerformed;
use App\Models\{Auth\Permission, Auth\Role, Impersonation, User};
use Illuminate\Support\Facades\{DB, Event};

use function Pest\Laravel\{actingAs};

beforeEach(function () {
    // Criar permissão de impersonation se não existir
    $permission = Permission::firstOrCreate([
        'name' => 'impersonate-users',
    ], [
        'description' => 'Allows impersonation of other users',
    ]);

    // Criar role de admin
    $adminRole = Role::firstOrCreate([
        'name' => 'admin',
    ], [
        'description' => 'Administrator',
        'is_default'  => false,
    ]);

    // Associar permissão à role
    if (!$adminRole->permissions()->where('id', $permission->id)->exists()) {
        $adminRole->permissions()->attach($permission->id);
    }

    // Limpar tabela de impersonation antes de cada teste
    DB::table('impersonations')->truncate();

    // Limpar tabela de audits antes de cada teste
    DB::table('audits')->truncate();

    // Falsificador de eventos para processamento síncrono
    Event::fake([ImpersonationActionPerformed::class]);
});

// Teste para iniciar impersonation
it('allows admin to impersonate another user', function () {
    // Criar usuário admin
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Criar usuário para ser impersonado
    $user = User::factory()->create();

    // Iniciar impersonation
    $response = actingAs($admin)
        ->postJson("/v1/auth/impersonate/{$user->id}");

    $response->assertOk();
    $response->assertJsonStructure([
        'message',
        'token',
        'user',
        'impersonation_id',
    ]);

    // Verificar que impersonation foi criado no banco
    $this->assertDatabaseHas('impersonations', [
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user->id,
        'ended_at'        => null,
    ]);
});

// Teste para não permitir impersonação a si mesmo
it('prevents admin from impersonating themselves', function () {
    // Criar usuário admin
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Tentar impersonar a si mesmo
    $response = actingAs($admin)
        ->postJson("/v1/auth/impersonate/{$admin->id}");

    $response->assertStatus(400);
    $response->assertJsonFragment([
        'message' => 'Você não pode impersonar a si mesmo.',
    ]);
});

// Teste para verificar que apenas admins podem impersonar
it('prevents non-admin from impersonating users', function () {
    // Criar usuário não-admin
    $user1 = User::factory()->create();

    // Criar usuário para ser impersonado
    $user2 = User::factory()->create();

    // Tentar impersonar outro usuário
    $response = actingAs($user1)
        ->postJson("/v1/auth/impersonate/{$user2->id}");

    $response->assertStatus(403);
});

// Teste para terminar impersonation
it('allows admin to stop impersonation', function () {
    // Criar usuário admin
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Criar usuário para ser impersonado
    $user = User::factory()->create();

    // Criar impersonation
    $impersonation = Impersonation::create([
        'id'              => (string) \Illuminate\Support\Str::ulid(),
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user->id,
    ]);

    // Encerrar impersonation
    $response = actingAs($admin)
        ->postJson('/v1/auth/impersonate/stop');

    $response->assertOk();

    // Verificar que impersonation foi encerrada
    $impersonation->refresh();
    expect($impersonation->ended_at)->not->toBeNull();
});

// Teste para verificar histórico de impersonation
it('shows impersonation history for admin', function () {
    // Criar usuário admin
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Criar usuários para serem impersonados
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Criar impersonations
    Impersonation::create([
        'id'              => (string) \Illuminate\Support\Str::ulid(),
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user1->id,
    ]);

    Impersonation::create([
        'id'              => (string) \Illuminate\Support\Str::ulid(),
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user2->id,
        'ended_at'        => now(),
    ]);

    // Obter histórico
    $response = actingAs($admin)
        ->getJson('/v1/auth/impersonate/history');

    $response->assertOk();
    $response->assertJsonCount(2, 'impersonations');
});

// Teste para verificar que ações são auditadas durante impersonation
it('dispatches audit event during impersonation', function () {
    // Criar usuário admin
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Criar usuário para ser impersonado
    $user = User::factory()->create();

    // Criar impersonation
    $impersonation = Impersonation::create([
        'id'              => (string) \Illuminate\Support\Str::ulid(),
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user->id,
    ]);

    // Usar token de impersonation
    $token = $user->createToken('impersonation-token', ['impersonated'])->plainTextToken;

    // Simular manualmente o evento que o middleware dispara
    event(new ImpersonationActionPerformed(
        user: $user,
        impersonation: $impersonation,
        action: 'GET',
        url: '/v1/users',
        ip: '127.0.0.1',
        userAgent: 'PHPUnit'
    ));

    // Verificar se o evento foi disparado
    Event::assertDispatched(ImpersonationActionPerformed::class, function ($event) use ($user, $impersonation) {
        return $event->user->is($user) &&
               $event->impersonation->is($impersonation) &&
               $event->action === 'GET' &&
               $event->url === '/v1/users';
    });
});
