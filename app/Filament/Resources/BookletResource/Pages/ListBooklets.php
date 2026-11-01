<?php

namespace App\Filament\Resources\BookletResource\Pages;

use App\Filament\Resources\BookletResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBooklets extends ListRecords
{
    protected static string $resource = BookletResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
