<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Traits\Auth\AuthenticatedUser;

class AuthMeAction
{
    use AuthenticatedUser;
    public function execute(): User
    {
        return $this->getAuthenticatedUser();
    }
}
