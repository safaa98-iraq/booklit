<?php

namespace App\Filament\Resources\BookletResource\Pages;

use App\Filament\Resources\BookletResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBooklet extends CreateRecord
{
    protected static string $resource = BookletResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()];
    }

    /** Go straight to the edit page so the editor can add topics and content. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
