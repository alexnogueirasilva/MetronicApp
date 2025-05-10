<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use Laravel\Sanctum\PersonalAccessToken;
use RuntimeException;

class LogoutAction
{
    public function execute(): void
    {
        $user = auth()->user();

        if ($user === null) {
            throw new RuntimeException('User not authenticated.');
        }

        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
