<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Student = 'student';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'مدير',
            self::Editor => 'محرر محتوى',
            self::Student => 'طالب',
        };
    }
}
