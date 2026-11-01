<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * @property Form $form
 */
class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'إعدادات التطبيق';

    protected static ?string $title = 'إعدادات التطبيق';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function mount(SettingsService $settings): void
    {
        $this->form->fill($settings->all());
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('المواد المقفلة والاشتراك')->columns(2)->schema([
                    Forms\Components\TextInput::make('telegram_url')->label('رابط حساب تيليجرام للإدارة')
                        ->url()->required()->placeholder('https://t.me/username')
                        ->extraInputAttributes(['dir' => 'ltr']),
                    Forms\Components\TextInput::make('support_phone')->label('رقم الدعم (اختياري)')->tel(),
                    Forms\Components\Textarea::make('locked_subject_message')->label('رسالة المادة المقفلة')
                        ->rows(3)->required()->columnSpanFull(),
                    Forms\Components\TextInput::make('locked_subject_button')->label('نص زر التواصل')->required(),
                ]),

                Forms\Components\Section::make('الزر الرابع في شاشة الملزمة')
                    ->description('زر قابل للتهيئة بجانب أزرار MCQ وزاري، MCQ الأستاذ، ووضع الاختبار.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('fourth_button.enabled')->label('إظهار الزر')->live()->columnSpanFull(),
                        Forms\Components\TextInput::make('fourth_button.label')->label('نص الزر')->required(),
                        Forms\Components\Select::make('fourth_button.icon')->label('الأيقونة')
                            ->options([
                                'star' => 'نجمة', 'bookmark' => 'إشارة مرجعية', 'video' => 'فيديو',
                                'chat' => 'محادثة', 'download' => 'تنزيل', 'info' => 'معلومات',
                            ])->default('star'),
                        Forms\Components\Select::make('fourth_button.action')->label('الوظيفة')
                            ->options(['none' => 'لا شيء (قريباً)', 'url' => 'فتح رابط', 'route' => 'شاشة داخل التطبيق'])
                            ->default('none')->required()->live(),
                        Forms\Components\TextInput::make('fourth_button.value')
                            ->label(fn (Get $get) => $get('fourth_button.action') === 'url' ? 'الرابط' : 'اسم الشاشة')
                            ->visible(fn (Get $get) => in_array($get('fourth_button.action'), ['url', 'route'], true))
                            ->required(fn (Get $get) => in_array($get('fourth_button.action'), ['url', 'route'], true))
                            ->url(fn (Get $get) => $get('fourth_button.action') === 'url')
                            ->extraInputAttributes(['dir' => 'ltr']),
                    ]),
            ]);
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->set([
            'telegram_url' => $data['telegram_url'],
            'support_phone' => $data['support_phone'] ?? null,
            'locked_subject_message' => $data['locked_subject_message'],
            'locked_subject_button' => $data['locked_subject_button'],
            'fourth_button' => [
                'enabled' => (bool) ($data['fourth_button']['enabled'] ?? false),
                'label' => $data['fourth_button']['label'] ?? '',
                'icon' => $data['fourth_button']['icon'] ?? 'star',
                'action' => $data['fourth_button']['action'] ?? 'none',
                'value' => $data['fourth_button']['value'] ?? null,
            ],
        ]);

        Notification::make()->title('تم حفظ الإعدادات')->success()->send();
    }
}
