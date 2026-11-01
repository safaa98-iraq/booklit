<?php

namespace App\Filament\Resources\QuestionResource\Pages;

use App\Enums\QuestionSource;
use App\Filament\Resources\QuestionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListQuestions extends ListRecords
{
    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),
            'ministerial' => Tab::make('وزاري')->modifyQueryUsing(fn ($query) => $query->where('source', QuestionSource::Ministerial)),
            'teacher' => Tab::make('الأستاذ')->modifyQueryUsing(fn ($query) => $query->where('source', QuestionSource::Teacher)),
        ];
    }
}
