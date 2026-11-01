<?php

namespace App\Support;

/**
 * Normalises Iraqi mobile numbers to the local 11-digit form (07XXXXXXXXX)
 * so "+964 770 123 4567", "00964770…", "٠٧٧٠…" all map to the same account.
 */
class Phone
{
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // Arabic-Indic and Persian digits → Latin.
        $phone = strtr($phone, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '00964')) {
            $digits = '0'.substr($digits, 5);
        } elseif (str_starts_with($digits, '964')) {
            $digits = '0'.substr($digits, 3);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '7')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
