<?php declare(strict_types = 1);

use App\Models\Auth\{Permission, Role};
use App\Models\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    Cache::flush();
});

it('assigns a role to user and checks it', function (): void {
    $user = User::factory()->create();
    $role = Role::factory()->create(['name' => 'admin']);

    $user->assignRole($role);

    assertDatabaseHas('role_user', [
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole($role))->toBeTrue();
});

it('assigns a role and checks permissions correctly', function (): void {
    $user       = User::factory()->create();
    $role       = Role::factory()->create();
    $permission = Permission::factory()->create(['name' => 'access-dashboard']);

    $role->permissions()->attach($permission);
    $user->assignRole($role);
    $user->refresh();
    $user->load('roles.permissions');

    expect($user->roles()->count())->toBe(1)
        ->and($user->hasRole($role))->toBeTrue()
        ->and($user->hasPermission('access-dashboard'))->toBeTrue();
});

it('returns permissions via role and caches them', function (): void {
    $user       = User::factory()->create();
    $permission = Permission::factory()->create(['name' => 'edit_users']);
    $role       = Role::factory()->create();

    $role->permissions()->attach($permission);
    $user->assignRole($role);
    $user->refresh();
    $user->load('roles.permissions');

    $permissions = $user->getCachedPermissions();

    expect($permissions)->toHaveCount(1)
        ->and($permissions->first()->name)->toBe('edit_users')
        ->and(Cache::has("user_{$user->id}_permissions"))->toBeTrue();
});

it('checks permission using hasPermission method', function (): void {
    $user       = User::factory()->create();
    $permission = Permission::factory()->create(['name' => 'view_orders']);
    $role       = Role::factory()->create();

    $role->permissions()->attach($permission);
    $user->assignRole($role);
    $user->refresh();
    $user->load('roles.permissions');

    expect($user->hasPermission('view_orders'))->toBeTrue();
});

it('clears permission cache', function (): void {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $user->assignRole($role);

    Cache::put("user_{$user->id}_permissions", collect(['fake']), now()->addMinutes(60));

    expect(Cache::has("user_{$user->id}_permissions"))->toBeTrue();

    $user->clearPermissionCache();

    expect(Cache::has("user_{$user->id}_permissions"))->toBeFalse();
});
