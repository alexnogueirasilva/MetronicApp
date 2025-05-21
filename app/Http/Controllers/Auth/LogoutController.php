<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LogoutController extends Controller
{
    /**
     * Log out the authenticated user
     *
     * This endpoint revokes the current authentication token and logs out the user.
     *
     * @group Auth
     * @authenticated
     *
     * @response {
     *     "message": "Logged out successfully."
     * }
     *
     * @response 401 {
     *     "message": "Unauthenticated."
     * }
     */
    public function __invoke(LogoutAction $action): JsonResponse
    {
        $action->execute();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
