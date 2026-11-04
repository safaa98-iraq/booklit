<?php

namespace App\Support;

use App\Enums\BlockType;

/**
 * Derives the canonical plain text of a content block.
 *
 * Passage-anchor offsets are code-point offsets into this string, so the
 * Flutter reader must build its text runs from exactly the same rules:
 *   - paragraph / heading / quote: data.text
 *   - list:  data.items joined with "\n"
 *   - math:  data.latex
 *   - table: each row's cells joined with " | ", rows joined with "\n"
 *   - image: data.caption (not anchorable)
 */
class BlockText
{
    public const CELL_SEPARATOR = ' | ';

    public static function plain(BlockType|string|null $type, array $data): string
    {
        $type = $type instanceof BlockType ? $type : BlockType::tryFrom((string) $type);

        $text = match ($type) {
            BlockType::Paragraph, BlockType::Heading, BlockType::Quote => (string) ($data['text'] ?? ''),
            BlockType::List => implode("\n", array_map('strval', array_values($data['items'] ?? []))),
            BlockType::Math => (string) ($data['latex'] ?? ''),
            BlockType::Image => (string) ($data['caption'] ?? ''),
            BlockType::Table => implode("\n", array_map(
                fn ($row) => implode(self::CELL_SEPARATOR, array_map('strval', (array) $row)),
                array_values($data['rows'] ?? []),
            )),
            default => '',
        };

        // Normalise line endings so offsets are identical on every client.
        return str_replace(["\r\n", "\r"], "\n", $text);
    }
}
