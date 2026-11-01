<?php

namespace App\Filament\Resources\BookletResource\RelationManagers;

use App\Filament\Forms\BlockEditor;
use App\Models\Topic;
use App\Services\BookletContentService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Topics of a booklet, each edited with the structured block editor.
 */
class TopicsRelationManager extends RelationManager
{
    protected static string $relationship = 'topics';

    protected static ?string $title = 'المواضيع والمحتوى';

    protected static ?string $modelLabel = 'موضوع';

    protected static ?string $pluralModelLabel = 'المواضيع';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->columns(1)->schema([
            Forms\Components\TextInput::make('title')->label('عنوان الموضوع')->required()->maxLength(255),
            BlockEditor::make('blocks'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->width('3rem'),
                Tables\Columns\TextColumn::make('title')->label('الموضوع')->weight('bold'),
                Tables\Columns\TextColumn::make('blocks_count')->label('عدد الكتل')->counts('blocks'),
                Tables\Columns\TextColumn::make('questions_count')->label('الأسئلة المرتبطة')->counts('questions'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver()->modalWidth(MaxWidth::FiveExtraLarge)
                    ->using(fn (array $data) => app(BookletContentService::class)->createTopic($this->getOwnerRecord(), $data)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تحرير المحتوى')
                    ->slideOver()->modalWidth(MaxWidth::FiveExtraLarge)
                    ->mutateRecordDataUsing(fn (array $data, Topic $record) => $data + [
                        'blocks' => app(BookletContentService::class)->editorState($record),
                    ])
                    ->using(fn (Topic $record, array $data) => app(BookletContentService::class)->updateTopic($record, $data)),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
