<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use InvalidArgumentException;

/**
 * All money is stored and computed as integer cents to avoid floating-point errors.
 */
final class Money
{
    public static function format(int $cents): string
    {
        $symbol = (string) Config::get('CURRENCY_SYMBOL', '$');
        $sign = $cents < 0 ? '-' : '';
        return $sign . $symbol . number_format(abs($cents) / 100, 2);
    }

    /** Parse a user-entered decimal string such as "12.5" into cents (1250) without using floats. */
    public static function toCents(string $amount): int
    {
        $amount = trim($amount);
        if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $m)) {
            throw new InvalidArgumentException("Invalid amount: {$amount}");
        }
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    public static function toDecimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
