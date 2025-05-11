<?php
declare(strict_types = 1);

use Illuminate\Support\Stringable;

function obfuscate_email(?string $email = null): string
{
    if (is_null($email) || in_array(strpos($email, '@'), [0, false], true)) {
        return '';
    }

    [$firstPart, $secondPart] = explode('@', $email);

    $qty             = (int) floor(strlen($firstPart) * 0.75);
    $remainingFirst  = strlen($firstPart) - $qty;
    $remainingSecond = strlen($secondPart) - $qty;

    $maskedFirstPart  = substr($firstPart, 0, $remainingFirst) . str_repeat('*', $qty);
    $maskedSecondPart = str_repeat('*', $qty) . substr($secondPart, $remainingSecond * -1, $remainingSecond);

    return $maskedFirstPart . '@' . $maskedSecondPart;
}

if (!function_exists('toString')) {
    /**
     * Convert a mixed value to a string.
     *
     * @throws JsonException
     */
    function toString(mixed $value): string
    {
        if ($value instanceof Stringable) {
            return (string) $value;
        }

        return match (true) {
            is_null($value)                       => '',
            is_array($value) || is_object($value) => json_encode($value, JSON_THROW_ON_ERROR),
            is_bool($value)                       => $value ? 'true' : 'false',
            is_resource($value)                   => 'resource',
            is_scalar($value)                     => (string) $value,
            default                               => throw new InvalidArgumentException('Unsupported type.'),
        };
    }
}

if (!function_exists('toInteger')) {
    /**
     * Convert a mixed value to a integer.
     */
    function toInteger(mixed $value): int
    {
        return match (true) {
            is_null($value)    => 0,
            is_bool($value)    => $value ? 1 : 0,
            is_numeric($value) => (int) $value,
            default            => throw new InvalidArgumentException('Unsupported type.'),
        };
    }
}

if (!function_exists('format_currency_br')) {
    /**
     * Format a number as Brazilian currency.
     */
    function format_currency_br(float $amount): string
    {
        return 'R$ ' . number_format($amount, 2, ',', '.');
    }
}

// Em um arquivo de helpers, por exemplo, app/Helpers/TypeConversion.php

if (!function_exists('as_nullable_string')) {
    /**
     * Converte um valor para string ou retorna nulo.
     */
    function as_nullable_string(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}

if (!function_exists('as_bool')) {
    /**
     * Converte um valor para bool se for booleano, ou retorna null.
     */
    function as_bool(mixed $value): ?bool
    {
        return is_bool($value) ? $value : null;
    }
}

if (!function_exists('decodeEmailBase64UrlSafe')) {
    /**
     * Decodifica uma string codificada em Base64 URL-safe para seu valor original.
     *
     * @param  string  $input  String codificada em Base64 URL-safe
     * @return string String decodificada
     *
     * @throws InvalidArgumentException Se a entrada não puder ser decodificada
     */
    function decodeEmailBase64UrlSafe(string $input): string
    {
        // Verifica se a entrada contém caracteres válidos
        if (in_array(preg_match('/^[a-zA-Z0-9\-_]*$/', $input), [0, false], true)) {
            throw new InvalidArgumentException('A entrada contém caracteres Base64 URL-safe inválidos');
        }

        $remainder = strlen($input) % 4;

        if ($remainder !== 0) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }

        $result = base64_decode(strtr($input, '-_', '+/'));

        if ($result === false) {
            throw new InvalidArgumentException('Não foi possível decodificar a string Base64');
        }

        return $result;
    }

}
