<?php declare(strict_types = 1);

namespace App\Enums;

/**
 * @property string $value
 */
enum TypeOtp: string
{
    case TOTP       = 'totp';
    case EMAIL      = 'email';
    case MAGIC_LINK = 'magic_link';
}
