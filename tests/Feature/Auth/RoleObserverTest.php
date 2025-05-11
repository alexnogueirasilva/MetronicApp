<?php declare(strict_types = 1);

use App\Models\Auth\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\assertDatabaseMissing;

it('clears permissions cache for users when role is updated', function (): void {
    $role  = Role::factory()->create();
    $users = User::factory()->count(2)->create();

    $users->each(fn (User $user) => $user->assignRole($role));

    // Finge que há cache
    $users->each(fn (User $user) => Cache::put("user_{$user->id}_permissions", collect(['mocked']), now()->addMinutes(60)));

    // Atualiza a role (dispara o observer)
    $role->update(['name' => 'Updated']);

    // Verifica se o cache foi limpo
    $users->each(fn (User $user) => expect(Cache::has("user_{$user->id}_permissions"))->toBeFalse());
});

it('clears permissions cache for users when role is deleted', function (): void {
    $role  = Role::factory()->create();
    $users = User::factory()->count(2)->create();

    $users->each(fn (User $user) => $user->assignRole($role));
    $users->each(fn (User $user) => Cache::put("user_{$user->id}_permissions", collect(['mocked']), now()->addMinutes(60)));

    $role->delete(); // Observer agora acessa os users corretamente

    $users->each(fn (User $user) => expect(Cache::has("user_{$user->id}_permissions"))->toBeFalse());

    assertDatabaseMissing('roles', ['id' => $role->id]);
});
