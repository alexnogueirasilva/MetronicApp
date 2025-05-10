<?php declare(strict_types = 1);

namespace App\Traits\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

trait AuthenticatedUser
{
    /**
     * Retorna o usuário autenticado com cache opcional.
     */
    public function getAuthenticatedUser(bool $useCache = true): User
    {

        $user = User::query()->where('id', auth()->id())->first();

        if (!$user) {
            throw new RuntimeException('Usuário não encontrado.');
        }

        if ($useCache) {
            /** @var User $cachedUser */
            $cachedUser = Cache::remember("authenticated_user::{$user->id}", now()->addMinutes(5), static fn (): User => $user);

            return $cachedUser;
        }

        return $user;
    }

    /**
     * Limpa o cache do usuário autenticado.
     */
    public function clearAuthenticatedUserCache(): void
    {
        Cache::forget('authenticated_user');
    }
}
