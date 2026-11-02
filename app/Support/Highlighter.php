<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Renders (escaped) text, wrapping the code-point range [start, end) of the
 * containing block's plain text in <mark>. $base is where $text starts
 * inside the block's plain text (used for list items).
 */
class Highlighter
{
    public static function render(string $text, int $base = 0, ?array $range = null): HtmlString
    {
        $length = mb_strlen($text);
        $from = $range ? max(0, min($length, $range['start'] - $base)) : 0;
        $to = $range ? max(0, min($length, $range['end'] - $base)) : 0;

        if (! $range || $from >= $to) {
            return new HtmlString(nl2br(e($text)));
        }

        return new HtmlString(
            nl2br(e(mb_substr($text, 0, $from)))
            .'<mark class="anchor">'.nl2br(e(mb_substr($text, $from, $to - $from))).'</mark>'
            .nl2br(e(mb_substr($text, $to)))
        );
    }

    /** Offset of each list item inside the block's plain text (items are joined with "\n"). */
    public static function listOffsets(array $items): array
    {
        $offsets = [];
        $offset = 0;
        foreach (array_values($items) as $i => $item) {
            $offsets[$i] = $offset;
            $offset += mb_strlen((string) $item) + 1;
        }

        return $offsets;
    }
}
