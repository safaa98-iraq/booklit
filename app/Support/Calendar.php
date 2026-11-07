<?php

namespace App\Support;

class Calendar
{
    public const MONTHS = [
        1 => 'كانون الثاني', 2 => 'شباط', 3 => 'آذار', 4 => 'نيسان',
        5 => 'أيار', 6 => 'حزيران', 7 => 'تموز', 8 => 'آب',
        9 => 'أيلول', 10 => 'تشرين الأول', 11 => 'تشرين الثاني', 12 => 'كانون الأول',
    ];

    /** Traditional Japanese month names, for the quiet decoration on each page. */
    public const WAFU = [
        1 => '睦月', 2 => '如月', 3 => '弥生', 4 => '卯月',
        5 => '皐月', 6 => '水無月', 7 => '文月', 8 => '葉月',
        9 => '長月', 10 => '神無月', 11 => '霜月', 12 => '師走',
    ];

    public const WEEKDAYS = [
        0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء',
        4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت',
    ];

    public static function monthName(int $month): string
    {
        return self::MONTHS[$month] ?? '';
    }

    public static function wafu(int $month): string
    {
        return self::WAFU[$month] ?? '';
    }
}
