<?php declare(strict_types = 1);

use App\Models\{Auth\Permission, Auth\Role, Impersonation, User};
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

use function Pest\Laravel\{actingAs, getJson};

beforeEach(function () {
    $permission = Permission::firstOrCreate([
        'name' => 'impersonate-users',
    ], [
        'description' => 'Allows impersonation of other users',
    ]);

    $adminRole = Role::firstOrCreate([
        'name' => 'admin',
    ], [
        'description' => 'Administrator',
        'is_default'  => false,
    ]);

    if (!$adminRole->permissions()->where('id', $permission->id)->exists()) {
        $adminRole->permissions()->attach($permission->id);
    }

    DB::table('impersonations')->truncate();

    DB::table('audits')->truncate();
});

it('allows admin to impersonate another user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    $response = actingAs($admin)
        ->postJson("/v1/auth/impersonate/{$user->id}");

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
        ->postJson("/v1/auth/impersonate/{$admin->id}");

    $response->assertStatus(400);
    $response->assertJsonFragment([
        'message' => 'Você não pode impersonar a si mesmo.',
    ]);
});

it('prevents non-admin from impersonating users', function () {
    $user1 = User::factory()->create();

    $user2 = User::factory()->create();

    $response = actingAs($user1)
        ->postJson("/v1/auth/impersonate/{$user2->id}");

    $response->assertStatus(403);
});

it('allows admin to stop impersonation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    $impersonation = Impersonation::create([
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user->id,
    ]);

    $response = actingAs($admin)
        ->postJson('/v1/auth/impersonate/stop');

    $response->assertOk();

    $impersonation->refresh();
    expect($impersonation->ended_at)->not->toBeNull();
});

it('shows impersonation history for admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Impersonation::create([
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user1->id,
    ]);

    Impersonation::create([
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user2->id,
        'ended_at'        => now(),
    ]);

    $response = actingAs($admin)
        ->getJson('/v1/auth/impersonate/history');

    $response->assertOk();
    $response->assertJsonCount(2, 'impersonations');
});

it('audits actions during impersonation', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create();

    $impersonation = Impersonation::create([
        'impersonator_id' => $admin->id,
        'impersonated_id' => $user->id,
    ]);

    Audit::create([
        'user_type'      => get_class($user),
        'user_id'        => $user->id,
        'event'          => 'impersonated-action',
        'auditable_type' => get_class($user),
        'auditable_id'   => $user->id,
        'old_values'     => [],
        'new_values'     => [
            'impersonator_id'  => $admin->id,
            'impersonation_id' => $impersonation->id,
            'action'           => 'GET',
            'url'              => '/v1/users',
            'ip'               => '127.0.0.1',
        ],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
    ]);

    $token = $user->createToken('impersonation-token', ['impersonated'])->plainTextToken;

    $response = getJson('/v1/users', [
        'Authorization' => "Bearer $token",
    ]);

    $this->assertDatabaseHas('audits', [
        'user_id'      => $user->id,
        'event'        => 'impersonated-action',
        'auditable_id' => $user->id,
    ]);
});
