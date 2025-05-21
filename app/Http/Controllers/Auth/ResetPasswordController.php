<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Enums\QueueEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Jobs\Auth\SendPasswordChangedNotificationJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use InvalidArgumentException;
use JsonException;

class ResetPasswordController extends Controller
{
    /**
     * Reset user password
     *
     * This endpoint allows a user to reset their password using a reset token.
     * It will validate the token and email, and then update the user's password.
     * A notification will be sent to confirm the password was changed.
     *
     * @group Auth
     * @unauthenticated
     *
     * @bodyParam email string required Base64 URL-safe encoded email address. Example: dXNlckBleGFtcGxlLmNvbQ
     * @bodyParam token string required The password reset token sent to the user's email. Example: 67d54c7c2a0d69c48f722eade81b1d24c7cde73b25e8784669a4061b770782fa
     * @bodyParam password string required The new password. Must be at least 8 characters. Example: new-password123
     * @bodyParam password_confirmation string required Must match the password field. Example: new-password123
     *
     * @response {
     *     "message": "Your password has been reset!"
     * }
     *
     * @response 422 {
     *     "message": "E-mail inválido ou corrompido."
     * }
     *
     * @response 422 {
     *     "message": "The given data was invalid.",
     *     "errors": {
     *         "token": ["This password reset token is invalid."],
     *         "password": ["The password must be at least 8 characters."]
     *     }
     * }
     *
     * @throws JsonException
     */
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $email = decodeEmailBase64UrlSafe(toString($request->input('email')));
        } catch (InvalidArgumentException) {
            return response()->json([
                'message' => 'E-mail inválido ou corrompido.',
            ], 422);
        }

        $status = Password::reset(
            [
                'email'                 => $email,
                'password'              => toString($request->input('password')),
                'password_confirmation' => toString($request->input('password_confirmation')),
                'token'                 => toString($request->input('token')),
            ],
            static function (User $user) use ($request): void {
                $user->forceFill([
                    'password' => toString($request->input('password')),
                ])->save();

                // Notificação de segurança sobre alteração de senha (alta prioridade)
                SendPasswordChangedNotificationJob::dispatch(
                    user: $user,
                    ip: toString($request->ip()),
                    userAgent: toString($request->userAgent())
                )->onQueue(QueueEnum::NOTIFICATIONS_HIGH->value);
            }
        );

        return response()->json([
            'message' => __(toString($status)),
        ], $status === Password::PASSWORD_RESET ? 200 : 422);
    }
}
