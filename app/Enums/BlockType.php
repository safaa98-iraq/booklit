<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BlockType: string implements HasLabel
{
    case Paragraph = 'paragraph';
    case Heading = 'heading';
    case Image = 'image';
    case List = 'list';
    case Math = 'math';
    case Quote = 'quote';
    case Table = 'table';

    public function getLabel(): string
    {
        return match ($this) {
            self::Paragraph => 'فقرة',
            self::Heading => 'عنوان',
            self::Image => 'صورة',
            self::List => 'قائمة',
            self::Math => 'معادلة رياضية (LaTeX)',
            self::Quote => 'اقتباس / ملاحظة',
            self::Table => 'جدول',
        };
    }

    /** Block types whose text can be referenced by a passage anchor. */
    public function isAnchorable(): bool
    {
        return in_array($this, [self::Paragraph, self::Heading, self::List, self::Quote, self::Math, self::Table], true);
    }
}
