<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LoginAction;
use App\DTO\Auth\LoginDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $dto = LoginDTO::fromRequest((array)$request->toDTO());

        return $action->execute($dto);
    }
}
