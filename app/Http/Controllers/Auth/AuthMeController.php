<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthMeAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;

class AuthMeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AuthMeAction $action): UserResource
    {
        return new UserResource($action->execute());
    }
}
