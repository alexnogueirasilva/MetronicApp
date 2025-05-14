<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Models\User;

/**
 * Action para gerar tokens de autenticação
 *
 * Esta classe tem a responsabilidade única de gerar
 * tokens de autenticação para usuários.
 */
class GenerateAuthTokenAction
{
    /**
     * Gera um novo token de autenticação para um usuário
     */
    public function execute(User $user, string $tokenName = 'api-token'): string
    {
        return $user->createToken($tokenName . '-' . now()->timestamp)->plainTextToken;
    }
}
