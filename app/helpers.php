<?php

use App\Support\Money;

if (! function_exists('money')) {
    /** Format an amount with the signed-in user's currency, e.g. "12,500 د.ع". */
    function money(float|string|null $amount, bool $withCurrency = true): string
    {
        $formatted = Money::format($amount);

        return $withCurrency ? $formatted.' '.(auth()->user()?->currency ?? '') : $formatted;
    }
}
