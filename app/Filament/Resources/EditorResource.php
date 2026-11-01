<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\EditorResource\Pages;
use App\Models\Subject;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class EditorResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'editors';

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'محرر';

    protected static ?string $pluralModelLabel = 'المحررون';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->editors();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('الاسم')->required()->maxLength(100),
                Forms\Components\TextInput::make('email')->label('البريد الإلكتروني')->email()->required()
                    ->rule(fn (?User $record) => Rule::unique('users', 'email')->ignore($record)),
                Forms\Components\TextInput::make('password')->label('كلمة المرور')
                    ->password()->revealable()->minLength(8)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state)),
                Forms\Components\Toggle::make('is_active')->label('مفعّل')->default(true)->inline(false),
                Forms\Components\Select::make('assignedSubjects')->label('المواد المسموح بتحريرها')
                    ->relationship('assignedSubjects', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Subject $record) => $record->full_name)
                    ->multiple()->preload()->searchable()->columnSpanFull()
                    ->helperText('يستطيع المحرر إنشاء وتعديل الملازم والأسئلة لهذه المواد فقط.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('البريد')->searchable(),
                Tables\Columns\TextColumn::make('assignedSubjects.name')->label('المواد')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('مفعّل')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('أضيف')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEditors::route('/'),
            'create' => Pages\CreateEditor::route('/create'),
            'edit' => Pages\EditEditor::route('/{record}/edit'),
        ];
    }

    public static function mutateRole(array $data): array
    {
        return ['role' => UserRole::Editor] + $data;
    }
}
