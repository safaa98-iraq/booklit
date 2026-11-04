<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Renders escaped text with highlighted ranges. Ranges are code-point offsets
 * into the containing block's plain text; $base is where $text starts inside
 * it (list items, table cells). Each range: ['start', 'end', 'class', 'id'?].
 */
class Highlighter
{
    public static function render(string $text, int $base = 0, array $ranges = []): HtmlString
    {
        $length = mb_strlen($text);

        // Clip ranges to this piece of text.
        $local = [];
        foreach ($ranges as $r) {
            $from = max(0, min($length, $r['start'] - $base));
            $to = max(0, min($length, $r['end'] - $base));
            if ($from < $to) {
                $local[] = ['start' => $from, 'end' => $to] + $r;
            }
        }

        if (! $local) {
            return new HtmlString(e($text));
        }

        // Split at every boundary; each segment gets the classes of the ranges covering it.
        $cuts = [0, $length];
        foreach ($local as $r) {
            $cuts[] = $r['start'];
            $cuts[] = $r['end'];
        }
        $cuts = array_values(array_unique($cuts));
        sort($cuts);

        $html = '';
        for ($i = 0; $i < count($cuts) - 1; $i++) {
            [$a, $b] = [$cuts[$i], $cuts[$i + 1]];
            $piece = e(mb_substr($text, $a, $b - $a));
            $cover = array_filter($local, fn ($r) => $r['start'] <= $a && $r['end'] >= $b);

            if (! $cover) {
                $html .= $piece;

                continue;
            }

            $classes = implode(' ', array_unique(array_column($cover, 'class')));
            $id = collect($cover)->firstWhere('id')['id'] ?? null;
            $html .= '<mark class="'.e($classes).'"'.($id ? ' data-hl="'.(int) $id.'"' : '').'>'.$piece.'</mark>';
        }

        return new HtmlString($html);
    }

    /** Offset of each piece inside the plain text when pieces are joined with $separatorLength chars. */
    public static function offsets(array $pieces, int $separatorLength = 1): array
    {
        $offsets = [];
        $offset = 0;
        foreach (array_values($pieces) as $i => $piece) {
            $offsets[$i] = $offset;
            $offset += mb_strlen((string) $piece) + $separatorLength;
        }

        return $offsets;
    }
}
