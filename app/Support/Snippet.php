<?php

namespace App\Support;

/** A short, single-line excerpt of $text around the first match of $query. */
class Snippet
{
    public static function around(string $text, string $query, int $radius = 45): string
    {
        $flat = preg_replace('/\s*\R\s*/u', ' · ', $text) ?? $text;
        $pos = mb_stripos($flat, $query);
        if ($pos === false) {
            return mb_strimwidth($flat, 0, $radius * 2, '…');
        }

        $start = max(0, $pos - $radius);
        $length = mb_strlen($query) + $radius * 2;

        return ($start > 0 ? '…' : '').mb_substr($flat, $start, $length).($start + $length < mb_strlen($flat) ? '…' : '');
    }
}
