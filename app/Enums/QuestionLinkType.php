<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * What a question is attached to. "none" means the question lives in the
 * standalone question bank of its subject (and therefore its stage).
 */
enum QuestionLinkType: string implements HasLabel
{
    case Topic = 'topic';
    case Booklet = 'booklet';
    case None = 'none';

    public function getLabel(): string
    {
        return match ($this) {
            self::Topic => 'موضوع محدد',
            self::Booklet => 'الملزمة كاملة',
            self::None => 'بنك أسئلة مستقل',
        };
    }
}
