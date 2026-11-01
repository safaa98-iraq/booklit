<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuestionSource: string implements HasColor, HasLabel
{
    case Ministerial = 'ministerial';
    case Teacher = 'teacher';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ministerial => 'وزاري',
            self::Teacher => 'الأستاذ',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ministerial => 'danger',
            self::Teacher => 'info',
        };
    }
}
