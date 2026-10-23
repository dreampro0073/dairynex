<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class Decimal
{
    public static function scaled(mixed $value, int $places, string $field = 'amount'): int
    {
        $value = (string) $value;

        if (! preg_match('/^(-?)(\d{1,12})(?:\.(\d{1,'.$places.'}))?$/D', $value, $matches)) {
            throw ValidationException::withMessages([$field => "Use a valid decimal with at most {$places} decimal places."]);
        }

        return ($matches[1] === '-' ? -1 : 1) * ((int) $matches[2] * (10 ** $places) + (int) str_pad($matches[3] ?? '', $places, '0'));
    }

    public static function format(int $value, int $places = 2): string
    {
        return ($value < 0 ? '-' : '').intdiv(abs($value), 10 ** $places).'.'.str_pad((string) (abs($value) % (10 ** $places)), $places, '0', STR_PAD_LEFT);
    }

    public static function charge(int $quantity, int $rate): int
    {
        // Thousandths of units × paise, rounded once at item level.
        if ($quantity < 0 || $rate < 0 || $quantity > 999999999 || $rate > 999999999) {
            throw ValidationException::withMessages(['items' => 'Quantity or rate exceeds the supported range.']);
        }

        return intdiv($quantity * $rate + 500, 1000);
    }
}
