<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Stage;
use App\Models\Subject;
use App\Models\User;
use App\Services\DeviceBindingService;
use App\Services\SubscriptionService;
use App\Support\Phone;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'students';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'الطلاب والاشتراكات';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->students();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone'];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الطالب')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('الاسم')->required()->maxLength(100),
                Forms\Components\TextInput::make('phone')->label('رقم الهاتف')
                    ->tel()->required()
                    ->dehydrateStateUsing(fn (?string $state) => Phone::normalize($state))
                    ->rule('regex:/^(\+?964|0)?7\d{9}$/')
                    ->rule(fn (?User $record) => Rule::unique('users', 'phone')->ignore($record)),
                Forms\Components\TextInput::make('password')->label('كلمة المرور')
                    ->password()->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->minLength(8)
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية.' : null),
                Forms\Components\Toggle::make('is_active')->label('الحساب مفعّل')->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('activeDevice')->withCount([
                'subscriptions as active_subscriptions_count' => fn ($s) => $s->currentlyActive(),
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('phone')->label('الهاتف')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('active_subscriptions_count')->label('اشتراكات فعّالة')
                    ->badge()->color(fn (int $state) => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('activeDevice.device_name')->label('الجهاز المرتبط')
                    ->description(fn (User $record) => $record->activeDevice?->platform)
                    ->placeholder('غير مرتبط'),
                Tables\Columns\IconColumn::make('is_active')->label('مفعّل')->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')->label('آخر دخول')->since()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ التسجيل')->date()->sortable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('subscribed')->label('مشترك')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('subscriptions', fn ($s) => $s->currentlyActive()),
                        false: fn (Builder $query) => $query->whereDoesntHave('subscriptions', fn ($s) => $s->currentlyActive()),
                    ),
                Tables\Filters\TernaryFilter::make('is_active')->label('الحساب مفعّل'),
            ])
            ->actions([
                static::activateSubscriptionAction(),
                static::resetDeviceAction(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    /** "تفعيل اشتراك": choose a stage or subjects and an expiry date. */
    public static function activateSubscriptionAction(): Action
    {
        return Action::make('activateSubscription')
            ->label('تفعيل اشتراك')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalHeading(fn (User $record) => "تفعيل اشتراك: {$record->name}")
            ->form(static::subscriptionFormSchema())
            ->action(fn (User $record, array $data) => static::activateSubscription($record, $data));
    }

    /** Shared by the table row action and the edit page header action. */
    public static function activateSubscription(User $student, array $data): void
    {
        app(SubscriptionService::class)->activate(
            student: $student,
            stageId: $data['scope'] === 'stage' ? (int) $data['stage_id'] : null,
            subjectIds: $data['scope'] === 'subjects' ? array_map('intval', $data['subject_ids'] ?? []) : [],
            expiresAt: Carbon::parse($data['expires_at'])->endOfDay(),
            activatedBy: auth()->user(),
            amount: filled($data['amount'] ?? null) ? (float) $data['amount'] : null,
            notes: $data['notes'] ?? null,
        );

        Notification::make()->title('تم تفعيل الاشتراك بنجاح')->success()->send();
    }

    /** Shared by the table row action and the edit page header action. */
    public static function resetDevice(User $student): void
    {
        app(DeviceBindingService::class)->reset($student, auth()->user());

        Notification::make()->title('تمت إعادة تعيين الجهاز')->success()->send();
    }

    public static function subscriptionFormSchema(): array
    {
        return [
            Forms\Components\Radio::make('scope')->label('نطاق الاشتراك')
                ->options(['stage' => 'مرحلة كاملة', 'subjects' => 'مواد محددة'])
                ->default('subjects')->inline()->required()->live(),
            Forms\Components\Select::make('stage_id')->label('المرحلة')
                ->options(fn () => Stage::ordered()->pluck('name', 'id'))
                ->visible(fn (Get $get) => $get('scope') === 'stage')
                ->required(fn (Get $get) => $get('scope') === 'stage'),
            Forms\Components\Select::make('subject_ids')->label('المواد')
                ->options(fn () => Subject::with('stage')->orderBy('stage_id')->get()
                    ->filter(fn (Subject $s) => $s->requiresSubscription())
                    ->mapWithKeys(fn (Subject $s) => [$s->id => $s->full_name]))
                ->multiple()->searchable()
                ->visible(fn (Get $get) => $get('scope') === 'subjects')
                ->required(fn (Get $get) => $get('scope') === 'subjects'),
            Forms\Components\DatePicker::make('expires_at')->label('تاريخ الانتهاء')
                ->native(false)->displayFormat('Y/m/d')
                ->minDate(now()->addDay()->startOfDay())
                ->default(now()->addMonths(9))
                ->required(),
            Forms\Components\TextInput::make('amount')->label('المبلغ المدفوع (اختياري)')->numeric()->suffix('د.ع'),
            Forms\Components\Textarea::make('notes')->label('ملاحظات')->rows(2),
        ];
    }

    public static function resetDeviceAction(): Action
    {
        return Action::make('resetDevice')
            ->label('إعادة تعيين الجهاز')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription('سيتم فك ارتباط الجهاز الحالي وتسجيل خروج الطالب، ويمكنه بعدها تسجيل الدخول من جهاز جديد.')
            ->visible(fn (User $record) => $record->activeDevice()->exists())
            ->action(fn (User $record) => static::resetDevice($record));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubscriptionsRelationManager::class,
            RelationManagers\DevicesRelationManager::class,
            RelationManagers\AttemptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    public static function mutateRole(array $data): array
    {
        return ['role' => UserRole::Student] + $data;
    }
}
