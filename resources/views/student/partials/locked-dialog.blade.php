@php($settings = app(\App\Services\SettingsService::class))
<dialog id="locked-dialog">
    <div class="dialog-body center">
        <div class="empty" style="padding:6px 0 0"><span class="ic">@include('student.partials.icon', ['name' => 'lock', 'size' => 28])</span></div>
        <h3>هذه المادة مقفلة</h3>
        <p class="muted">{{ session('locked') ?: 'هذه المادة مقفلة ومتاحة للمشتركين فقط.' }}</p>
        <div class="stack">
            @if ($settings->get('telegram_url'))
                <a class="btn btn-telegram btn-block" href="{{ $settings->get('telegram_url') }}" target="_blank" rel="noopener">@include('student.partials.icon', ['name' => 'send', 'size' => 18]) تواصل عبر تيليجرام</a>
            @endif
            <button class="btn btn-outline btn-block" type="button" data-close>إغلاق</button>
        </div>
    </div>
</dialog>
