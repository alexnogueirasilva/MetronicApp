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
     * Handle the incoming request.
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
