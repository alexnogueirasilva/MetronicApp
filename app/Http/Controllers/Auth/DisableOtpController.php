<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\DisableOtpAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DisableOtpController extends Controller
{
    public function __invoke(DisableOtpAction $action): JsonResponse
    {
        $action->execute();

        return response()->json([
            'message' => 'Autenticação OTP desativada com sucesso.',
        ]);
    }
}
