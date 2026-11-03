@php($settings = app(\App\Services\SettingsService::class))
<dialog id="locked-dialog">
    <div class="dialog-body">
        <div style="color:var(--muted)">@include('student.partials.icon', ['name' => 'lock', 'size' => 44])</div>
        <h3>المادة مقفلة</h3>
        <p class="muted" id="locked-message">{{ session('locked') ?: $settings->get('locked_subject_message') }}</p>
        <div class="stack">
            @if ($settings->get('telegram_url'))
                <a class="btn btn-telegram btn-block" href="{{ $settings->get('telegram_url') }}" target="_blank" rel="noopener">
                    @include('student.partials.icon', ['name' => 'send', 'size' => 18]) {{ $settings->get('locked_subject_button') }}
                </a>
            @endif
            <button class="btn btn-ghost btn-block" type="button" data-close>إغلاق</button>
        </div>
    </div>
</dialog>
