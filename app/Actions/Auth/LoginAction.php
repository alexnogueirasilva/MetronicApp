<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\DTO\Auth\LoginDTO;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class LoginAction
{
    public function execute(LoginDTO $dto): JsonResponse
    {
        $user = User::query()
            ->where('email', $dto->email)
            ->with(['role.permissions'])
            ->firstOrFail();

        // Ensure password is a string
        $password = $user->password ?? '';

        if (!Hash::check($dto->password, $password)) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken($dto->device)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }
}
