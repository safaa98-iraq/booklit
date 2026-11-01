<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('activateSubscription')
                ->label('تفعيل اشتراك')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->form(StudentResource::subscriptionFormSchema())
                ->action(function (array $data) {
                    StudentResource::activateSubscription($this->record, $data);
                    $this->dispatch('refreshRelationManagers');
                }),
            Actions\Action::make('resetDevice')
                ->label('إعادة تعيين الجهاز')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('سيتم فك ارتباط الجهاز الحالي وتسجيل خروج الطالب، ويمكنه بعدها تسجيل الدخول من جهاز جديد.')
                ->visible(fn () => $this->record->activeDevice()->exists())
                ->action(fn () => StudentResource::resetDevice($this->record)),
            Actions\DeleteAction::make(),
        ];
    }
}
