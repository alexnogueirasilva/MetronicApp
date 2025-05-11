<?php declare(strict_types = 1);

namespace App\Actions\Auth;

use App\DTO\Auth\TotpSecretDTO;
use InvalidArgumentException;
use OTPHP\TOTP;

class TotpManagerAction
{
    public function generate(string $email): TotpSecretDTO
    {

        if ($email === '' || $email === '0') {
            throw new InvalidArgumentException('Email cannot be empty.');
        }

        $totp = TOTP::create();
        $totp->setLabel($email);

        return new TotpSecretDTO(
            secret: $totp->getSecret(),
            qr: $totp->getProvisioningUri(),
        );
    }

    public function verify(string $secret, string $code): bool
    {
        if ($secret === '' || $secret === '0' || ($code === '' || $code === '0')) {
            throw new InvalidArgumentException('Secret and code cannot be empty.');
        }

        return TOTP::create($secret)->verify($code);
    }
}
