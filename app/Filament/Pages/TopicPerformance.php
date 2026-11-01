<?php

namespace App\Filament\Pages;

use App\Models\Subject;
use App\Models\User;
use App\Services\ReportService;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Report: per-topic performance per student. */
class TopicPerformance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'أداء الطلاب حسب الموضوع';

    protected static ?string $title = 'أداء كل طالب في كل موضوع';

    protected static string $view = 'filament.pages.report-table';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(app(ReportService::class)->topicPerformance())
            ->defaultKeySort(false)
            ->defaultSort('accuracy', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('student.name')->label('الطالب')
                    ->description(fn ($record) => $record->student?->phone),
                Tables\Columns\TextColumn::make('topic.booklet.subject.name')->label('المادة'),
                Tables\Columns\TextColumn::make('topic.booklet.title')->label('الملزمة'),
                Tables\Columns\TextColumn::make('topic.title')->label('الموضوع'),
                Tables\Columns\TextColumn::make('answered')->label('عدد الأسئلة المحلولة')->sortable(),
                Tables\Columns\TextColumn::make('correct')->label('الصحيحة')->sortable(),
                Tables\Columns\TextColumn::make('accuracy')->label('نسبة الإتقان')->suffix('%')->sortable()
                    ->badge()->color(fn ($state) => $state >= 75 ? 'success' : ($state >= 50 ? 'warning' : 'danger')),
            ])
            ->filters([
                Filter::make('student')
                    ->form([
                        Select::make('user_id')->label('الطالب')->searchable()
                            ->getSearchResultsUsing(fn (string $search) => User::students()
                                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                                ->limit(50)->pluck('name', 'id')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['user_id'] ?? null, fn ($q, $id) => $q->where('attempts.user_id', $id))),
                Filter::make('subject')
                    ->form([
                        Select::make('subject_id')->label('المادة')
                            ->options(fn () => Subject::with('stage')->get()->mapWithKeys(fn (Subject $s) => [$s->id => $s->full_name])),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['subject_id'] ?? null, fn ($q, $id) => $q->where('attempts.subject_id', $id))),
            ]);
    }
}
