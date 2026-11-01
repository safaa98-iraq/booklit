<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StageResource\Pages;
use App\Models\Stage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StageResource extends Resource
{
    protected static ?string $model = Stage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'مرحلة';

    protected static ?string $pluralModelLabel = 'المراحل';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('اسم المرحلة')->required()->maxLength(255),
                Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
                Forms\Components\Textarea::make('description')->label('الوصف')->columnSpanFull(),
                Forms\Components\Toggle::make('is_ministerial')->label('مرحلة وزارية (مدفوعة)')
                    ->helperText('جميع مواد المرحلة الوزارية مقفلة إلا للمشتركين.'),
                Forms\Components\Toggle::make('is_active')->label('مفعّلة')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('المرحلة')->searchable(),
                Tables\Columns\TextColumn::make('subjects_count')->label('عدد المواد')->counts('subjects'),
                Tables\Columns\IconColumn::make('is_ministerial')->label('وزارية')->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')->label('مفعّلة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStages::route('/'),
        ];
    }
}
