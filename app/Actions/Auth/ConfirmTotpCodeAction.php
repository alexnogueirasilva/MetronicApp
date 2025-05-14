<?php
declare(strict_types = 1);

namespace App\Actions\Auth;

use App\Enums\TypeOtp;
use App\Traits\Auth\AuthenticatedUser;
use OTPHP\TOTP;
use Symfony\Component\HttpFoundation\Response;

class ConfirmTotpCodeAction
{
    use AuthenticatedUser;

    /**
     * Confirma o código TOTP e ativa a autenticação.
     *
     * @param  array{code: string}  $input
     */
    public function execute(array $input): void
    {
        $user = $this->getAuthenticatedUser();

        $secret = trim((string) $user->totp_secret);
        $code   = trim($input['code']);

        if ($secret === '' || $code === '') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Código TOTP inválido.');
        }

        $totp = TOTP::create($secret);

        if (!$totp->verify($code)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Código TOTP inválido.');
        }

        $user->forceFill([
            'otp_method'    => TypeOtp::TOTP,
            'totp_verified' => true,
        ])->save();
    }
}
