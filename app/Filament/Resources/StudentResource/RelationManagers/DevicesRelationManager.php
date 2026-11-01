<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DevicesRelationManager extends RelationManager
{
    protected static string $relationship = 'devices';

    protected static ?string $title = 'سجل الأجهزة';

    protected static ?string $modelLabel = 'جهاز';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('bound_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('device_name')->label('الجهاز')->placeholder('—'),
                Tables\Columns\TextColumn::make('platform')->label('النظام')->badge(),
                Tables\Columns\TextColumn::make('device_id')->label('المعرّف')->limit(16)->copyable(),
                Tables\Columns\TextColumn::make('bound_at')->label('تاريخ الربط')->dateTime('Y/m/d H:i'),
                Tables\Columns\TextColumn::make('last_seen_at')->label('آخر نشاط')->since(),
                Tables\Columns\TextColumn::make('released_at')->label('فُكّ الارتباط')->dateTime('Y/m/d H:i')->placeholder('مرتبط حالياً'),
            ]);
    }
}
