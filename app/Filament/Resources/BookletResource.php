<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookletResource\Pages;
use App\Filament\Resources\BookletResource\RelationManagers\TopicsRelationManager;
use App\Models\Booklet;
use App\Models\Subject;
use App\Support\Media;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookletResource extends Resource
{
    protected static ?string $model = Booklet::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'ملزمة';

    protected static ?string $pluralModelLabel = 'الملازم';

    protected static ?string $recordTitleAttribute = 'title';

    /** Editors only ever see booklets of their assigned subjects. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->manageableBy(auth()->user());
    }

    /** @return array<int, string> */
    public static function subjectOptions(): array
    {
        return Subject::query()->manageableBy(auth()->user())->with('stage')
            ->orderBy('stage_id')->orderBy('sort_order')->get()
            ->mapWithKeys(fn (Subject $s) => [$s->id => $s->full_name])
            ->all();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الملزمة')->columns(2)->schema([
                Forms\Components\Select::make('subject_id')->label('المادة')
                    ->options(fn () => static::subjectOptions())
                    ->in(fn () => array_keys(static::subjectOptions()))
                    ->required()->searchable(),
                Forms\Components\TextInput::make('title')->label('عنوان الملزمة')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->label('الوصف')->columnSpanFull(),
                Forms\Components\FileUpload::make('cover_image')->label('صورة الغلاف')
                    ->image()->disk(Media::disk())->directory('booklets/covers')->visibility('public'),
                Forms\Components\Grid::make(1)->columnSpan(1)->schema([
                    Forms\Components\TextInput::make('quiz_question_count')->label('عدد أسئلة وضع الاختبار')
                        ->helperText('عدد الأسئلة التي تُسحب عشوائياً عند بدء وضع الاختبار.')
                        ->numeric()->minValue(1)->maxValue(200)->default(10)->required(),
                    Forms\Components\TextInput::make('sort_order')->label('الترتيب')->numeric()->default(0),
                    Forms\Components\Toggle::make('is_published')->label('منشورة للطلاب'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('الملزمة')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('subject.name')->label('المادة')->sortable(),
                Tables\Columns\TextColumn::make('subject.stage.name')->label('المرحلة'),
                Tables\Columns\TextColumn::make('topics_count')->label('المواضيع')->counts('topics'),
                Tables\Columns\TextColumn::make('questions_count')->label('الأسئلة')->counts('questions'),
                Tables\Columns\IconColumn::make('is_published')->label('منشورة')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('آخر تعديل')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')->label('المادة')->options(fn () => static::subjectOptions()),
                Tables\Filters\TernaryFilter::make('is_published')->label('منشورة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [TopicsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBooklets::route('/'),
            'create' => Pages\CreateBooklet::route('/create'),
            'edit' => Pages\EditBooklet::route('/{record}/edit'),
        ];
    }
}
