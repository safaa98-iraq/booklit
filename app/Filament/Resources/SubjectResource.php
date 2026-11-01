<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use App\Support\Media;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'مادة';

    protected static ?string $pluralModelLabel = 'المواد';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\Select::make('stage_id')->label('المرحلة')
                    ->relationship('stage', 'name')->required()->preload()->searchable(),
                Forms\Components\TextInput::make('name')->label('اسم المادة')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->label('الوصف')->columnSpanFull(),
                Forms\Components\FileUpload::make('cover_image')->label('صورة البطاقة')
                    ->image()->disk(Media::disk())->directory('subjects')->visibility('public'),
                Forms\Components\ColorPicker::make('color')->label('لون البطاقة'),
                Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
                Forms\Components\Toggle::make('is_ministerial')->label('مادة وزارية (مقفلة)')
                    ->helperText('المواد الوزارية تظهر بقفل ولا تُفتح إلا بعد تفعيل الاشتراك.'),
                Forms\Components\Toggle::make('is_active')->label('مفعّلة')->default(true),
                Forms\Components\Select::make('editors')->label('المحررون المسؤولون')
                    ->relationship('editors', 'name', fn ($query) => $query->editors())
                    ->multiple()->preload()->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ColorColumn::make('color')->label(''),
                Tables\Columns\TextColumn::make('name')->label('المادة')->searchable(),
                Tables\Columns\TextColumn::make('stage.name')->label('المرحلة')->sortable(),
                Tables\Columns\TextColumn::make('booklets_count')->label('الملازم')->counts('booklets'),
                Tables\Columns\TextColumn::make('editors.name')->label('المحررون')->badge(),
                Tables\Columns\IconColumn::make('is_ministerial')->label('وزارية')->boolean()
                    ->trueIcon('heroicon-o-lock-closed')->falseIcon('heroicon-o-lock-open'),
                Tables\Columns\ToggleColumn::make('is_active')->label('مفعّلة'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage_id')->label('المرحلة')->relationship('stage', 'name'),
                Tables\Filters\TernaryFilter::make('is_ministerial')->label('وزارية'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubjects::route('/'),
        ];
    }
}
