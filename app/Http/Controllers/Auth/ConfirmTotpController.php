<?php declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ConfirmTotpCodeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTotpRequest;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class ConfirmTotpController extends Controller
{
    public function __invoke(ConfirmTotpRequest $request, ConfirmTotpCodeAction $action): JsonResponse
    {
        $validated = $request->validated();

        if (!isset($validated['code']) || !is_string($validated['code'])) {
            throw new InvalidArgumentException('The validated data must contain a string "code".');
        }

        $action->execute(['code' => $validated['code']]);

        return response()->json([
            'message' => 'TOTP ativado com sucesso.',
        ]);
    }
}
