<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Enums\QueueEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Jobs\Auth\SendForgotPasswordEmailJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    /**
     * Request password reset email
     *
     * This endpoint sends a password reset link to the provided email address,
     * if it exists in the system. For security reasons, it always returns success
     * even if the email is not registered.
     *
     * @group Auth
     * @unauthenticated
     *
     * @bodyParam email string required The email address to send the reset link to. Example: user@example.com
     *
     * @response {
     *     "message": "If your email is registered, you will receive a password reset link."
     * }
     *
     * @response 422 {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "email": [
     *             "The email field is required.",
     *             "The email must be a valid email address."
     *         ]
     *     }
     * }
     */
    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->input('email');

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            SendForgotPasswordEmailJob::dispatch($user)
                ->onQueue(QueueEnum::AUTH_CRITICAL->value);
        }

        return response()->json([
            'message' => 'If your email is registered, you will receive a password reset link.',
        ]);
    }
}
