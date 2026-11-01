<?php

namespace App\Filament\Resources;

use App\Enums\Difficulty;
use App\Enums\QuestionLinkType;
use App\Enums\QuestionSource;
use App\Filament\Forms\Components\PassageAnchorPicker;
use App\Filament\Resources\QuestionResource\Pages;
use App\Models\Booklet;
use App\Models\ContentBlock;
use App\Models\Question;
use App\Models\Topic;
use App\Support\Media;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'المحتوى';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'سؤال';

    protected static ?string $pluralModelLabel = 'الأسئلة';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->manageableBy(auth()->user());
    }

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\Group::make()->columnSpan(2)->schema([
                Forms\Components\Section::make('نص السؤال')->schema([
                    Forms\Components\Textarea::make('text')->label('السؤال')->rows(3)->autosize()->required(),
                    Forms\Components\FileUpload::make('image')->label('صورة (اختياري)')
                        ->image()->disk(Media::disk())->directory('questions')->visibility('public')->maxSize(5120),
                ]),

                Forms\Components\Section::make('الخيارات')
                    ->description('من 2 إلى 6 خيارات، مع خيار صحيح واحد فقط. تُعرض للطالب بترتيب عشوائي.')
                    ->schema([
                        Forms\Components\Repeater::make('options')->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->schema([
                                Forms\Components\Textarea::make('text')->label('نص الخيار')->rows(1)->autosize()->required()->columnSpan(5),
                                Forms\Components\Toggle::make('is_correct')->label('صحيح')
                                    ->fixIndistinctState()->inline(false)->columnSpan(1),
                            ])
                            ->columns(6)
                            ->minItems(Question::MIN_OPTIONS)
                            ->maxItems(Question::MAX_OPTIONS)
                            ->defaultItems(4)
                            ->addActionLabel('إضافة خيار')
                            ->reorderableWithButtons()
                            ->rule(fn () => function (string $attribute, mixed $value, Closure $fail) {
                                $correct = collect($value)->filter(fn ($o) => (bool) ($o['is_correct'] ?? false))->count();
                                if ($correct !== 1) {
                                    $fail('يجب تحديد خيار صحيح واحد فقط.');
                                }
                            }),
                    ]),

                Forms\Components\Section::make('الشرح')->schema([
                    Forms\Components\Textarea::make('explanation')->label('شرح الإجابة')->rows(3)->autosize(),
                ]),

                Forms\Components\Section::make('ربط بالنص في الملزمة')
                    ->description('حدد المقطع الذي يُميَّز للطالب داخل الملزمة عند إظهار الإجابة.')
                    ->visible(fn (Get $get) => filled($get('booklet_id')) && $get('link_type') !== QuestionLinkType::None->value)
                    ->schema([
                        Forms\Components\Repeater::make('anchors')->hiddenLabel()
                            ->relationship()
                            ->maxItems(3)
                            ->defaultItems(0)
                            ->addActionLabel('إضافة مقطع')
                            ->schema([
                                Forms\Components\Select::make('content_block_id')->label('الكتلة النصية')
                                    ->options(fn (Get $get) => static::anchorableBlocks($get('../../booklet_id'), $get('../../topic_id')))
                                    ->searchable()->required()->live()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('start_offset', null);
                                        $set('end_offset', null);
                                    }),
                                PassageAnchorPicker::make('picker'),
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('start_offset')->label('بداية المقطع')
                                        ->numeric()->minValue(0)->required(),
                                    Forms\Components\TextInput::make('end_offset')->label('نهاية المقطع')
                                        ->numeric()->required()->gt('start_offset')
                                        ->rule(fn (Get $get) => function (string $attribute, mixed $value, Closure $fail) use ($get) {
                                            $text = ContentBlock::whereKey($get('content_block_id'))->value('plain_text');
                                            if ($text !== null && (int) $value > mb_strlen($text)) {
                                                $fail('نهاية المقطع تتجاوز طول النص.');
                                            }
                                        }),
                                ]),
                            ]),
                    ]),
            ]),

            Forms\Components\Group::make()->columnSpan(1)->schema([
                Forms\Components\Section::make('التصنيف')->schema([
                    Forms\Components\Select::make('subject_id')->label('المادة')
                        ->options(fn () => BookletResource::subjectOptions())
                        ->in(fn () => array_keys(BookletResource::subjectOptions()))
                        ->required()->searchable()->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('booklet_id', null);
                            $set('topic_id', null);
                        }),
                    Forms\Components\ToggleButtons::make('source')->label('المصدر')
                        ->options(QuestionSource::class)->inline()->required()->live(),
                    Forms\Components\TextInput::make('source_reference')->label('مرجع السؤال الوزاري')
                        ->placeholder('مثال: وزاري 2024 الدور الأول')
                        ->visible(fn (Get $get) => $get('source') === QuestionSource::Ministerial->value),
                    Forms\Components\ToggleButtons::make('difficulty')->label('الصعوبة')
                        ->options(Difficulty::class)->inline()->default(Difficulty::Medium->value)->required(),
                    Forms\Components\Toggle::make('is_active')->label('مفعّل')->default(true),
                ]),

                Forms\Components\Section::make('الارتباط')->schema([
                    Forms\Components\Radio::make('link_type')->label('يرتبط السؤال بـ')
                        ->options(QuestionLinkType::class)
                        ->default(QuestionLinkType::None->value)->required()->live(),
                    Forms\Components\Select::make('booklet_id')->label('الملزمة')
                        ->options(fn (Get $get) => Booklet::where('subject_id', $get('subject_id'))->orderBy('sort_order')->pluck('title', 'id'))
                        ->visible(fn (Get $get) => in_array($get('link_type'), [QuestionLinkType::Topic->value, QuestionLinkType::Booklet->value], true))
                        ->required(fn (Get $get) => in_array($get('link_type'), [QuestionLinkType::Topic->value, QuestionLinkType::Booklet->value], true))
                        ->in(fn (Get $get) => Booklet::where('subject_id', $get('subject_id'))->pluck('id')->all())
                        ->searchable()->live()
                        ->afterStateUpdated(fn (Set $set) => $set('topic_id', null)),
                    Forms\Components\Select::make('topic_id')->label('الموضوع')
                        ->options(fn (Get $get) => Topic::where('booklet_id', $get('booklet_id'))->orderBy('sort_order')->pluck('title', 'id'))
                        ->visible(fn (Get $get) => $get('link_type') === QuestionLinkType::Topic->value)
                        ->required(fn (Get $get) => $get('link_type') === QuestionLinkType::Topic->value)
                        ->in(fn (Get $get) => Topic::where('booklet_id', $get('booklet_id'))->pluck('id')->all())
                        ->searchable()->live(),
                ]),
            ]),
        ]);
    }

    /** Blocks of the chosen topic (or the whole booklet) that can be anchored. */
    public static function anchorableBlocks(mixed $bookletId, mixed $topicId): array
    {
        if (! $bookletId) {
            return [];
        }

        return ContentBlock::query()
            ->with('topic:id,title')
            ->whereHas('topic', fn ($q) => $q->where('booklet_id', $bookletId))
            ->when($topicId, fn ($q) => $q->where('topic_id', $topicId))
            ->whereIn('type', ['paragraph', 'heading', 'list', 'quote', 'math'])
            ->join('topics', 'topics.id', '=', 'content_blocks.topic_id')
            ->orderBy('topics.sort_order')->orderBy('content_blocks.sort_order')
            ->get(['content_blocks.*'])
            ->mapWithKeys(fn (ContentBlock $b) => [$b->id => "{$b->topic->title} — ".Str::limit(Str::squish($b->plain_text), 90)])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('text')->label('السؤال')->limit(70)->searchable()->wrap(),
                Tables\Columns\TextColumn::make('subject.name')->label('المادة')->sortable(),
                Tables\Columns\TextColumn::make('booklet.title')->label('الملزمة')->placeholder('بنك مستقل')->toggleable(),
                Tables\Columns\TextColumn::make('topic.title')->label('الموضوع')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('source')->label('المصدر')->badge(),
                Tables\Columns\TextColumn::make('difficulty')->label('الصعوبة')->badge(),
                Tables\Columns\TextColumn::make('options_count')->label('الخيارات')->counts('options'),
                Tables\Columns\IconColumn::make('anchors_exists')->label('مقطع مرتبط')->exists('anchors')->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')->label('مفعّل'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')->label('المادة')->options(fn () => BookletResource::subjectOptions()),
                Tables\Filters\SelectFilter::make('booklet_id')->label('الملزمة')->relationship('booklet', 'title', fn (Builder $query) => $query->manageableBy(auth()->user())),
                Tables\Filters\SelectFilter::make('source')->label('المصدر')->options(QuestionSource::class),
                Tables\Filters\SelectFilter::make('difficulty')->label('الصعوبة')->options(Difficulty::class),
                Tables\Filters\SelectFilter::make('link_type')->label('الارتباط')->options(QuestionLinkType::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()->label('نسخ')
                    ->excludeAttributes(['options_count', 'anchors_exists'])
                    ->after(function (Question $replica, Question $record) {
                        foreach ($record->options as $option) {
                            $replica->options()->create($option->only(['text', 'image', 'is_correct', 'sort_order']));
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
