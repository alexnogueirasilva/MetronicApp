<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\LogoutAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class LogoutController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LogoutAction $action): JsonResponse
    {
        $action->execute();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
