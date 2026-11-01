<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Attributes\On;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    protected static ?string $title = 'الاشتراكات';

    protected static ?string $modelLabel = 'اشتراك';

    #[On('refreshRelationManagers')]
    public function refresh(): void {}

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('expires_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('scope_label')->label('النطاق'),
                Tables\Columns\TextColumn::make('starts_at')->label('من')->date('Y/m/d'),
                Tables\Columns\TextColumn::make('expires_at')->label('إلى')->date('Y/m/d'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge()
                    ->state(fn (Subscription $r) => match (true) {
                        $r->revoked_at !== null => 'ملغى',
                        $r->expires_at->isPast() => 'منتهي',
                        $r->starts_at->isFuture() => 'لم يبدأ',
                        default => 'فعّال',
                    })
                    ->color(fn (string $state) => $state === 'فعّال' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('amount')->label('المبلغ')->numeric()->placeholder('—'),
                Tables\Columns\TextColumn::make('activator.name')->label('فعّله')->placeholder('—'),
            ])
            ->actions([
                Tables\Actions\Action::make('revoke')
                    ->label('إلغاء')
                    ->icon('heroicon-o-x-circle')->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Subscription $r) => $r->revoked_at === null && $r->expires_at->isFuture())
                    ->action(fn (Subscription $r) => app(SubscriptionService::class)->revoke($r)),
            ]);
    }
}
