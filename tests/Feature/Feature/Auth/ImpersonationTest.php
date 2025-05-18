<?php declare(strict_types = 1);

use App\Events\ImpersonationActionPerformed;
use App\Models\{Auth\Permission, Auth\Role, Impersonation, User};
use Illuminate\Support\Facades\{DB, Event};
use Illuminate\Support\Str;

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

it('allows admin to impersonate another user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    $response = actingAs($admin)
        ->postJson(route('auth.impersonate.start', $user->id));

    $response->assertOk();
    $response->assertJsonStructure([
        'message',
        'token',
        'user',
        'impersonation_id',
    ]);

    $this->assertDatabaseHas('impersonations', [
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user->id,
        'ended_at'        => null,
    ]);
});

it('prevents admin from impersonating themselves', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = actingAs($admin)
        ->postJson(route('auth.impersonate.start', $admin->id));

    $response->assertStatus(400);
    $response->assertJsonFragment([
        'message' => 'Você não pode impersonar a si mesmo.',
    ]);
});

it('prevents non-admin from impersonating users', function () {
    $user1 = User::factory()->create();

    $user2 = User::factory()->create();

    $response = actingAs($user1)
        ->postJson(route('auth.impersonate.start', $user2->id));

    $response->assertStatus(403);
});

it('allows admin to stop impersonation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    $impersonation = Impersonation::query()->create([
        'id'              => (string) Str::ulid(),
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user->id,
    ]);

    $response = actingAs($admin)
        ->postJson(route('auth.impersonate.stop', $impersonation->id));

    $response->assertOk();

    $impersonation->refresh();
    expect($impersonation->ended_at)->not->toBeNull();
});

it('shows impersonation history for admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Impersonation::query()->create([
        'id'              => (string) Str::ulid(),
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user1->id,
    ]);

    Impersonation::query()->create([
        'id'              => (string) Str::ulid(),
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user2->id,
        'ended_at'        => now(),
    ]);

    $response = actingAs($admin)
        ->getJson(route('auth.impersonate.history'));

    $response->assertOk();
    $response->assertJsonCount(2, 'impersonations');
});

it('dispatches audit event during impersonation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    $impersonation = Impersonation::query()->create([
        'id'              => (string) Str::ulid(),
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user->id,
    ]);

    $token = $user->createToken('impersonation-token', ['impersonated'])->plainTextToken;

    event(new ImpersonationActionPerformed(
        user: $user,
        impersonation: $impersonation,
        action: 'GET',
        url: '/v1/users',
        ip: '127.0.0.1',
        userAgent: 'PHPUnit'
    ));

    Event::assertDispatched(ImpersonationActionPerformed::class, static function ($event) use ($user, $impersonation) {
        return $event->user->is($user) &&
               $event->impersonation->is($impersonation) &&
               $event->action === 'GET' &&
               $event->url === '/v1/users';
    });
});
