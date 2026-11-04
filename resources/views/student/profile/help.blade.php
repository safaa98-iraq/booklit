@extends('student.layout')
@section('title', 'المساعدة والدعم')
@section('tab', 'profile')
@section('content')
@include('student.partials.appbar', ['title' => 'المساعدة والدعم', 'back' => route('student.profile')])
<main class="page">
    <div class="card card-body">
        <b>تحتاج مساعدة؟</b>
        <p class="muted">للاشتراك في المواد الوزارية، أو إعادة تعيين الجهاز، أو أي استفسار، تواصل مع الإدارة مباشرة.</p>
        @if ($settings->get('telegram_url'))
            <a class="btn btn-telegram btn-block" href="{{ $settings->get('telegram_url') }}" target="_blank" rel="noopener">@include('student.partials.icon', ['name' => 'send', 'size' => 18]) تواصل عبر تيليجرام</a>
        @endif
        @if ($settings->get('support_phone'))
            <p class="center" style="margin:12px 0 0">أو اتصل: <b dir="ltr">{{ $settings->get('support_phone') }}</b></p>
        @endif
    </div>
    <div class="section"><div class="section-title"><h2>أسئلة شائعة</h2></div>
        <div class="card list">
            @foreach ([
                ['كيف أفتح مادة مقفلة؟', 'المواد الوزارية تحتاج اشتراكاً. تواصل مع الإدارة عبر تيليجرام، وبعد الدفع يتم تفعيل المادة لحسابك.'],
                ['لماذا لا أستطيع الدخول من جهاز آخر؟', 'كل حساب مرتبط بجهاز واحد لحماية المحتوى. اطلب من الإدارة إعادة تعيين الجهاز.'],
                ['كيف يُحسب تقدمي؟', 'يُعتبر الموضوع مكتملاً عند قراءته حتى نهايته تقريباً، ويُحفظ موضع قراءتك تلقائياً.'],
            ] as [$q, $a])
                <details class="item" style="display:block"><summary style="font-weight:700;cursor:pointer">{{ $q }}</summary><p class="muted" style="margin:8px 0 0">{{ $a }}</p></details>
            @endforeach
        </div>
    </div>
</main>
@endsection
