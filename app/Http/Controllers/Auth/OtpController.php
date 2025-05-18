<?php
declare(strict_types = 1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\{GenerateOtpCodeAction, SetupTotpAction, VerifyOtpCodeAction};
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\{RequestOtpCodeRequest, VerifyOtpCodeRequest, VerifyTotpCodeRequest};
use App\Traits\Auth\AuthenticatedUser;
use Illuminate\Http\JsonResponse;
use JsonException;
use Random\RandomException;

class OtpController extends Controller
{
    use AuthenticatedUser;

    /**
     * Request an OTP code via email
     *
     * This endpoint generates and sends a one-time password to the provided email address.
     * The OTP code can be used for authentication or verification purposes.
     *
     * @group Auth
     * @unauthenticated
     *
     * @bodyParam email string required The email address to send the OTP code to. Example: user@example.com
     *
     * @response {
     *     "message": "Código enviado por e-mail."
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
     *
     * @throws RandomException
     * @throws JsonException
     */
    public function requestEmailCode(RequestOtpCodeRequest $request, GenerateOtpCodeAction $action): JsonResponse
    {
        $action->__invoke(toString($request->email));

        return response()->json(['message' => 'Código enviado por e-mail.']);
    }

    /**
     * Handle the incoming request.
     *
     * @throws RandomException|JsonException
     * @throws JsonException
     */
    public function __invoke(RequestOtpCodeRequest $request, GenerateOtpCodeAction $action): JsonResponse
    {
        $action->__invoke(toString($request->input('email')));

        return response()->json([
            'message' => 'OTP code sent successfully.',
        ]);
    }

    /**
     * @throws JsonException
     */
    public function verifyEmailCode(VerifyOtpCodeRequest $request, VerifyOtpCodeAction $action): JsonResponse
    {
        $success = $action->viaEmail(toString($request->email), toString($request->code));

        return response()->json(['valid' => $success], $success ? 200 : 422);
    }

    public function setupTotp(SetupTotpAction $action): JsonResponse
    {
        $dto = $action->__invoke($this->getAuthenticatedUser());

        return response()->json([
            'secret' => $dto->secret,
            'qr'     => $dto->qr,
        ]);
    }

    /**
     * @throws JsonException
     */
    public function verifyTotp(VerifyTotpCodeRequest $request, VerifyOtpCodeAction $action): JsonResponse
    {
        $success = $action->viaTotp($this->getAuthenticatedUser(), toString($request->code));

        return response()->json(['valid' => $success], $success ? 200 : 422);
    }
}
