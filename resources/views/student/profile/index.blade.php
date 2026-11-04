@extends('student.layout')
@section('title', 'حسابي')
@section('tab', 'profile')
@section('content')
@include('student.partials.appbar', ['title' => 'حسابي'])
<main class="page">
    @if (session('status'))<div class="alert alert-ok">{{ session('status') }}</div>@endif

    <div class="card">
        <div class="card-body profile-head">
            <span class="avatar">{{ mb_substr($student->name, 0, 1) }}</span>
            <b style="font-size:1.2rem;display:block">{{ $student->name }}</b>
            <div class="muted small">{{ $student->email ?: 'لا يوجد بريد إلكتروني' }} · <span dir="ltr">{{ $student->phone }}</span></div>
            <a class="btn btn-light btn-sm" style="margin-top:12px" href="{{ route('student.profile.edit') }}">@include('student.partials.icon', ['name' => 'pen', 'size' => 16]) تعديل الحساب</a>
        </div>
        <div class="stats" style="border-top:1px solid var(--line)">
            <div><b>{{ $stats['booklets'] }}</b><span>الملازم</span></div>
            <div><b>{{ $stats['accuracy'] }}%</b><span>نسبة الدقة</span></div>
            <div><b>{{ \App\Services\ProgressService::formatDuration($stats['seconds']) }}</b><span>وقت الدراسة</span></div>
        </div>
    </div>

    @if ($subscriptions->isNotEmpty())
        <div class="section">
            <div class="section-title"><h2>اشتراكاتي</h2></div>
            <div class="card list">
                @foreach ($subscriptions as $sub)
                    <div class="item"><span class="ic">@include('student.partials.icon', ['name' => 'check', 'size' => 18])</span>
                        <span class="grow"><b>{{ $sub->stage?->name ?? $sub->subject?->name }}</b><br><span class="muted small">ينتهي في {{ $sub->expires_at->format('Y/m/d') }}</span></span></div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="section">
        <div class="card list">
            @foreach ([
                ['pen', 'تعديل الحساب', route('student.profile.edit')],
                ['key', 'تغيير كلمة المرور', route('student.profile.password')],
                ['bell', 'الإشعارات', route('student.profile.preferences', 'notifications')],
                ['palette', 'المظهر', route('student.profile.preferences', 'appearance')],
                ['globe', 'اللغة', route('student.profile.preferences', 'language')],
                ['heart', 'المفضلة', route('student.favorites')],
                ['history', 'شوهد مؤخراً', route('student.profile.recent')],
                ['clipboard', 'نتائج الاختبارات', route('student.profile.attempts')],
                ['help', 'المساعدة والدعم', route('student.profile.help')],
                ['info', 'عن BOOKLIT', route('student.profile.about')],
            ] as [$icon, $label, $url])
                <a class="item" href="{{ $url }}">
                    <span class="ic">@include('student.partials.icon', ['name' => $icon, 'size' => 18])</span>
                    <span class="grow">{{ $label }}</span>
                    <span class="chev">@include('student.partials.icon', ['name' => 'chev', 'size' => 16])</span>
                </a>
            @endforeach
            <form method="POST" action="{{ route('student.logout') }}" class="item" style="padding:0">
                @csrf
                <button type="submit" class="tool-row" style="padding:14px 16px;color:var(--red);border:0">
                    <span class="ic" style="background:var(--red-soft);color:var(--red)">@include('student.partials.icon', ['name' => 'logout', 'size' => 18])</span>
                    <span class="grow">تسجيل الخروج</span>
                </button>
            </form>
        </div>
        <p class="center muted small" style="margin-top:14px">الحساب مرتبط بهذا الجهاز. لتغيير الجهاز تواصل مع الإدارة.</p>
    </div>
</main>
@endsection
