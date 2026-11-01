<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'نتائج المحاولات';

    protected static ?string $modelLabel = 'محاولة';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('booklet.title')->label('الملزمة')->placeholder('بنك الأسئلة'),
                Tables\Columns\TextColumn::make('subject.name')->label('المادة'),
                Tables\Columns\TextColumn::make('mode')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('difficulty')->label('الصعوبة')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('score')->label('الدرجة')->suffix('%')->placeholder('—'),
                Tables\Columns\TextColumn::make('correct_count')->label('الصحيحة')
                    ->formatStateUsing(fn ($state, $record) => "{$state} / {$record->total_questions}"),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge(),
                Tables\Columns\TextColumn::make('submitted_at')->label('التاريخ')->dateTime('Y/m/d H:i')->placeholder('—'),
            ]);
    }
}
