<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value application settings editable from the admin panel.
 */
class SettingsService
{
    private const CACHE_KEY = 'booklit.settings';

    /** Defaults for every known key. */
    public function defaults(): array
    {
        return [
            'telegram_url' => config('booklit.defaults.telegram_url'),
            'locked_subject_message' => 'هذه المادة وزارية ومتاحة للمشتركين فقط. للاشتراك تواصل مع الإدارة عبر تيليجرام.',
            'locked_subject_button' => 'تواصل عبر تيليجرام',
            'support_phone' => null,
            // Configurable 4th button on the booklet screen.
            'fourth_button' => [
                'enabled' => false,
                'label' => 'قريباً',
                'icon' => 'star',
                'action' => 'none',   // none | url | route
                'value' => null,      // URL or app route name
            ],
        ];
    }

    public function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());

        $merged = $this->defaults();
        foreach ($stored as $key => $value) {
            $merged[$key] = is_array($merged[$key] ?? null) && is_array($value)
                ? array_replace($merged[$key], $value)
                : $value;
        }

        return $merged;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }

    /** Settings exposed to the student app. */
    public function publicSettings(): array
    {
        $all = $this->all();

        return [
            'telegram_url' => $all['telegram_url'],
            'locked_subject_message' => $all['locked_subject_message'],
            'locked_subject_button' => $all['locked_subject_button'],
            'support_phone' => $all['support_phone'],
            'fourth_button' => $all['fourth_button'],
        ];
    }
}
