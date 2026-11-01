<?php

namespace App\Filament\Resources;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Filament\Resources\AttemptResource\Pages;
use App\Models\Attempt;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Report: score per student per attempt. */
class AttemptResource extends Resource
{
    protected static ?string $model = Attempt::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'محاولة';

    protected static ?string $pluralModelLabel = 'درجات الطلاب';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'subject', 'booklet']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('الطالب')->searchable()
                    ->description(fn (Attempt $r) => $r->user?->phone),
                Tables\Columns\TextColumn::make('subject.name')->label('المادة')->sortable(),
                Tables\Columns\TextColumn::make('booklet.title')->label('الملزمة')->placeholder('بنك الأسئلة'),
                Tables\Columns\TextColumn::make('mode')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('difficulty')->label('الصعوبة')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('score')->label('الدرجة')->suffix('%')->sortable()
                    ->color(fn ($state) => $state === null ? 'gray' : ($state >= 50 ? 'success' : 'danger')),
                Tables\Columns\TextColumn::make('correct_count')->label('الصحيحة')
                    ->formatStateUsing(fn ($state, Attempt $r) => "{$state} / {$r->total_questions}"),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge(),
                Tables\Columns\TextColumn::make('submitted_at')->label('تاريخ الإرسال')->dateTime('Y/m/d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')->label('الطالب')
                    ->relationship('user', 'name', fn (Builder $query) => $query->students())->searchable(),
                Tables\Filters\SelectFilter::make('subject_id')->label('المادة')->relationship('subject', 'name'),
                Tables\Filters\SelectFilter::make('booklet_id')->label('الملزمة')->relationship('booklet', 'title'),
                Tables\Filters\SelectFilter::make('mode')->label('النوع')->options(AttemptMode::class),
                Tables\Filters\SelectFilter::make('status')->label('الحالة')->options(AttemptStatus::class)
                    ->default(AttemptStatus::Submitted->value),
            ])
            ->actions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttempts::route('/'),
        ];
    }
}
