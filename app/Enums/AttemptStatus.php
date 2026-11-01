<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttemptStatus: string implements HasLabel
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';

    public function getLabel(): string
    {
        return match ($this) {
            self::InProgress => 'قيد الحل',
            self::Submitted => 'تم الإرسال',
        };
    }
}
