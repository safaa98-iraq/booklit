<?php

namespace App\Enums;

/** The four pillars of kakeibo. */
enum Category: string
{
    case Needs = 'needs';
    case Wants = 'wants';
    case Culture = 'culture';
    case Unexpected = 'unexpected';

    public function label(): string
    {
        return match ($this) {
            self::Needs => 'الضروريات',
            self::Wants => 'الرغبات',
            self::Culture => 'الثقافة',
            self::Unexpected => 'الطوارئ',
        };
    }

    public function kanji(): string
    {
        return match ($this) {
            self::Needs => '必要',
            self::Wants => '欲望',
            self::Culture => '文化',
            self::Unexpected => '予想外',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Needs => 'طعام، مواصلات، فواتير، دواء',
            self::Wants => 'مطاعم، تسوّق، ترفيه',
            self::Culture => 'كتب، دورات، متاحف، موسيقى',
            self::Unexpected => 'تصليحات، هدايا، ما لم يكن في الحسبان',
        };
    }

    /** CSS colour token name for this category. */
    public function tone(): string
    {
        return match ($this) {
            self::Needs => 'ai',
            self::Wants => 'beni',
            self::Culture => 'matcha',
            self::Unexpected => 'kincha',
        };
    }
}
