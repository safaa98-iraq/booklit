<?php

namespace App\Filament\Forms;

use App\Enums\BlockType;
use App\Support\Media;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

/**
 * Structured block editor for booklet content.
 *
 * Each block carries a hidden `block_uuid` — the stable ID that passage
 * anchors reference. BookletContentService preserves it on save.
 */
class BlockEditor
{
    public static function make(string $name = 'blocks'): Builder
    {
        return Builder::make($name)
            ->label('محتوى الموضوع')
            ->addActionLabel('إضافة كتلة')
            ->blocks([
                Block::make(BlockType::Paragraph->value)
                    ->label(BlockType::Paragraph->getLabel())
                    ->icon('heroicon-o-bars-3-bottom-right')
                    ->schema([
                        self::uuid(),
                        Textarea::make('text')->label('النص')->rows(5)->autosize()->required(),
                    ]),

                Block::make(BlockType::Heading->value)
                    ->label(BlockType::Heading->getLabel())
                    ->icon('heroicon-o-hashtag')
                    ->columns(4)
                    ->schema([
                        self::uuid(),
                        TextInput::make('text')->label('العنوان')->required()->columnSpan(3),
                        Select::make('level')->label('المستوى')
                            ->options([2 => 'رئيسي', 3 => 'فرعي', 4 => 'صغير'])
                            ->default(2)->required()->selectablePlaceholder(false),
                    ]),

                Block::make(BlockType::Image->value)
                    ->label(BlockType::Image->getLabel())
                    ->icon('heroicon-o-photo')
                    ->schema([
                        self::uuid(),
                        FileUpload::make('path')->label('الصورة')
                            ->image()->imageEditor()
                            ->disk(Media::disk())->directory('booklets/images')->visibility('public')
                            ->maxSize(5120)->required(),
                        TextInput::make('caption')->label('التعليق (اختياري)'),
                        TextInput::make('alt')->label('وصف بديل للصورة'),
                    ]),

                Block::make(BlockType::List->value)
                    ->label(BlockType::List->getLabel())
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        self::uuid(),
                        Select::make('style')->label('نوع القائمة')
                            ->options(['bullet' => 'نقطية', 'ordered' => 'مرقمة'])
                            ->default('bullet')->required()->selectablePlaceholder(false),
                        Repeater::make('items')->label('العناصر')
                            ->simple(TextInput::make('text')->required())
                            ->addActionLabel('إضافة عنصر')
                            ->reorderableWithButtons()
                            ->minItems(1)->defaultItems(2),
                    ]),

                Block::make(BlockType::Math->value)
                    ->label(BlockType::Math->getLabel())
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        self::uuid(),
                        Textarea::make('latex')->label('صيغة LaTeX')
                            ->placeholder('\\frac{a}{b} = c^2')
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'font-family: monospace'])
                            ->rows(3)->required(),
                        Toggle::make('display')->label('عرض في سطر مستقل')->default(true),
                    ]),

                Block::make(BlockType::Quote->value)
                    ->label(BlockType::Quote->getLabel())
                    ->icon('heroicon-o-light-bulb')
                    ->schema([
                        self::uuid(),
                        Select::make('variant')->label('النوع')
                            ->options(['note' => 'ملاحظة', 'tip' => 'معلومة إضافية', 'warning' => 'تنبيه', 'definition' => 'تعريف'])
                            ->default('note')->required()->selectablePlaceholder(false),
                        Textarea::make('text')->label('النص')->rows(3)->autosize()->required(),
                    ]),
            ])
            ->blockNumbers(false)
            ->collapsible()
            ->cloneable()
            ->reorderableWithButtons()
            ->columnSpanFull();
    }

    private static function uuid(): Hidden
    {
        return Hidden::make('block_uuid');
    }
}
