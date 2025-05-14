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
     * Handle the incoming request.
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
