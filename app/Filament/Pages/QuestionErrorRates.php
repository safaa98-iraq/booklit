<?php

namespace App\Filament\Pages;

use App\Enums\QuestionSource;
use App\Models\Question;
use App\Models\Subject;
use App\Services\ReportService;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/** Report: per-question error rate (hardest questions first). */
class QuestionErrorRates extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'الأسئلة الأصعب';

    protected static ?string $title = 'نسبة الخطأ لكل سؤال';

    protected static string $view = 'filament.pages.report-table';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(app(ReportService::class)->questionErrorRates())
            ->defaultSort('error_rate', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('text')->label('السؤال')->limit(80)->wrap()->searchable(),
                Tables\Columns\TextColumn::make('subject.name')->label('المادة'),
                Tables\Columns\TextColumn::make('booklet.title')->label('الملزمة')->placeholder('بنك مستقل'),
                Tables\Columns\TextColumn::make('topic.title')->label('الموضوع')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('source')->label('المصدر')->badge(),
                Tables\Columns\TextColumn::make('difficulty')->label('الصعوبة')->badge(),
                Tables\Columns\TextColumn::make('answered_count')->label('عدد الإجابات')->sortable(),
                Tables\Columns\TextColumn::make('wrong_count')->label('الخاطئة')->sortable(),
                Tables\Columns\TextColumn::make('error_rate')->label('نسبة الخطأ')->suffix('%')->sortable()
                    ->badge()->color(fn ($state) => $state >= 60 ? 'danger' : ($state >= 30 ? 'warning' : 'success')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')->label('المادة')
                    ->options(fn () => Subject::with('stage')->get()->mapWithKeys(fn (Subject $s) => [$s->id => $s->full_name])),
                Tables\Filters\SelectFilter::make('source')->label('المصدر')->options(QuestionSource::class),
            ])
            ->recordUrl(fn (Question $record) => route('filament.admin.resources.questions.edit', $record));
    }
}
