<?php

namespace App\Filament\Widgets;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\Booklet;
use App\Models\Question;
use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected function getStats(): array
    {
        $submitted = Attempt::where('status', AttemptStatus::Submitted);

        return [
            Stat::make('الطلاب المسجلون', User::students()->count())
                ->description('منهم '.User::students()->whereDate('created_at', today())->count().' اليوم')
                ->icon('heroicon-o-users'),
            Stat::make('الاشتراكات الفعّالة', Subscription::currentlyActive()->distinct('user_id')->count('user_id'))
                ->icon('heroicon-o-check-badge')->color('success'),
            Stat::make('الملازم المنشورة', Booklet::published()->count())
                ->description(Question::count().' سؤال')
                ->icon('heroicon-o-book-open'),
            Stat::make('محاولات اليوم', (clone $submitted)->whereDate('submitted_at', today())->count())
                ->description('متوسط الدرجات: '.round((float) (clone $submitted)->avg('score'), 1).'%')
                ->icon('heroicon-o-clipboard-document-check'),
        ];
    }
}
