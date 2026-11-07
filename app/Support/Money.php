<?php

namespace App\Support;

class Money
{
    /** 12500 → "12,500", 12.5 → "12.50". */
    public static function format(float|string|null $amount): string
    {
        $amount = (float) $amount;
        $decimals = floor($amount) == $amount ? 0 : 2;

        return number_format($amount, $decimals);
    }
}
