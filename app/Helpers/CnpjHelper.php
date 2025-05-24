<?php declare(strict_types = 1);

namespace App\Helpers;

class CnpjHelper
{
    public static function isValid(string $cnpj): bool
    {
        $cnpj = self::sanitize($cnpj);

        if (strlen($cnpj) !== 14 || preg_match("/^{$cnpj[0]}{14}$/", $cnpj)) {
            return false;
        }

        $cnpjDigits = array_map('intval', str_split($cnpj));

        $multipliers1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $multipliers2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum1 = array_sum(array_map(
            static fn ($digit, $multiplier): int => $digit * $multiplier,
            array_slice($cnpjDigits, 0, 12),
            $multipliers1
        ));

        $digit1 = $sum1 % 11;
        $digit1 = $digit1 < 2 ? 0 : 11 - $digit1;

        $sum2 = array_sum(array_map(
            static fn ($digit, $multiplier): int => $digit * $multiplier,
            array_slice($cnpjDigits, 0, 12),
            $multipliers2
        ));
        $sum2 += $digit1 * $multipliers2[12];

        $digit2 = $sum2 % 11;
        $digit2 = $digit2 < 2 ? 0 : 11 - $digit2;

        return $cnpjDigits[12] === $digit1 && $cnpjDigits[13] === $digit2;
    }

    public static function sanitize(string $cnpj): string
    {
        return preg_replace('/\D/', '', $cnpj) ?? '';
    }
}
