<?php declare(strict_types = 1);

namespace App\Services\Authorize;

use App\Exceptions\Authorize\AuthorizationException;
use App\Traits\Auth\AuthenticatedUser;
use BackedEnum;
use Illuminate\Support\Facades\Auth;

class AuthorizeAccount
{
    use AuthenticatedUser;

    /**
     * Autoriza o acesso baseado na permissão, senão lança exceção.
     *
     * @throws AuthorizationException
     */
    public static function authorize(BackedEnum $permission): void
    {
        if (!self::hasPermission($permission)) {
            throw new AuthorizationException();
        }
    }

    /**
     * Verifica se o usuário autenticado tem a permissão fornecida.
     */
    public static function hasPermission(BackedEnum $permission): bool
    {
        $user = Auth::user();

        return $user && $user->hasPermission((string)$permission->value);
    }
}
