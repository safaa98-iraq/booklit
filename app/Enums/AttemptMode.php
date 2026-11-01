<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttemptMode: string implements HasLabel
{
    case Ministerial = 'ministerial';
    case Teacher = 'teacher';
    case Quiz = 'quiz';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ministerial => 'MCQ وزاري',
            self::Teacher => 'MCQ الأستاذ',
            self::Quiz => 'وضع الاختبار',
        };
    }

    /** The question source an MCQ mode draws from; quiz mode mixes both. */
    public function source(): ?QuestionSource
    {
        return match ($this) {
            self::Ministerial => QuestionSource::Ministerial,
            self::Teacher => QuestionSource::Teacher,
            self::Quiz => null,
        };
    }
}
