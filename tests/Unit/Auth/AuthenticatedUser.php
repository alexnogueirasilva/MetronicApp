<?php declare(strict_types = 1);

use App\Models\User;
use App\Traits\Auth\AuthenticatedUser;
use Illuminate\Support\Facades\{Auth, Cache};

beforeEach(function (): void {
    Cache::flush();
});

// Dummy class pra testar o trait isoladamente
class DummyAuthClass
{
    use AuthenticatedUser;
}

it('returns authenticated user without cache', function (): void {
    $user = User::factory()->create();

    Auth::shouldReceive('id')->once()->andReturn($user->id);

    $instance = new DummyAuthClass();

    $result = $instance->getAuthenticatedUser(useCache: false);

    expect($result)->toBeInstanceOf(User::class)
        ->and($result->id)->toBe($user->id);
});

it('returns authenticated user with cache', function (): void {
    $user = User::factory()->create();

    Auth::shouldReceive('id')->twice()->andReturn($user->id);

    $instance = new DummyAuthClass();

    $firstCall  = $instance->getAuthenticatedUser(useCache: true);
    $secondCall = $instance->getAuthenticatedUser(useCache: true);

    expect($firstCall->id)->toBe($user->id)
        ->and($secondCall->id)->toBe($user->id);
});

it('throws exception when no user is authenticated', function (): void {
    Auth::shouldReceive('id')->once()->andReturn(null);

    $instance = new DummyAuthClass();

    $instance->getAuthenticatedUser(); // this will throw
})->throws(RuntimeException::class, 'Usuário não encontrado.');

it('clears the authenticated user cache', function (): void {
    $user = User::factory()->create();

    Auth::shouldReceive('id')->once()->andReturn($user->id);

    $instance = new DummyAuthClass();

    $instance->getAuthenticatedUser();

    expect(Cache::has("authenticated_user::{$user->id}"))->toBeTrue();

    Cache::shouldReceive('forget')
        ->once()
        ->with('authenticated_user')
        ->andReturn(true);

    $instance->clearAuthenticatedUserCache();
});
