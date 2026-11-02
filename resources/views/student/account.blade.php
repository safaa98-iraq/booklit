@extends('student.layout')
@section('title', 'حسابي')
@section('back', route('student.home'))
@section('content')
@php($settings = app(\App\Services\SettingsService::class))
<main class="container narrow" style="padding:16px 16px 40px">
    <div class="card"><div class="card-body" style="display:flex;align-items:center;gap:14px">
        <span class="avatar" style="width:54px;height:54px;font-size:1.3rem">{{ mb_substr($student->name, 0, 1) }}</span>
        <div><strong style="font-size:1.15rem">{{ $student->name }}</strong><div class="muted" dir="ltr" style="text-align:right">{{ $student->phone }}</div></div>
    </div></div>

    <h3>اشتراكاتي</h3>
    <div class="card">
        @forelse ($subscriptions as $sub)
            <div class="list-item">
                <span style="color:var(--green)">@include('student.partials.icon', ['name' => 'badge'])</span>
                <span class="grow"><strong>{{ $sub->stage?->name ?? $sub->subject?->name }}</strong><br><span class="muted small">ينتهي في {{ $sub->expires_at->format('Y/m/d') }}</span></span>
            </div>
        @empty
            <div class="card-body muted">لا توجد اشتراكات فعّالة. المواد المجانية متاحة لك دائماً.</div>
        @endforelse
    </div>

    <h3>محاولاتي</h3>
    <div class="card">
        @forelse ($attempts as $a)
            <a class="list-item" href="{{ route('student.attempts.show', $a) }}">
                @if ($a->isSubmitted())
                    <span class="score-badge" style="background:{{ $a->score >= 50 ? 'var(--green-soft)' : 'var(--red-soft)' }}">{{ round($a->score) }}%</span>
                @else
                    <span class="score-badge" style="background:var(--orange-soft)">جارٍ</span>
                @endif
                <span class="grow">
                    <strong>{{ $a->mode->getLabel() }}</strong>@if ($a->difficulty) <span class="pill pill-{{ $a->difficulty->value }}">{{ $a->difficulty->getLabel() }}</span>@endif<br>
                    <span class="muted small">{{ $a->booklet?->title ?? $a->subject?->name }} · {{ ($a->submitted_at ?? $a->started_at)?->format('Y/m/d') }}</span>
                </span>
                <span class="muted small">{{ $a->isSubmitted() ? $a->correct_count.'/'.$a->total_questions : 'أكمل الحل' }}</span>
            </a>
        @empty
            <div class="card-body muted">لم تحل أي اختبار بعد.</div>
        @endforelse
    </div>

    <div style="display:grid;gap:10px;margin-top:20px">
        @if ($settings->get('telegram_url'))
            <a class="btn btn-telegram" href="{{ $settings->get('telegram_url') }}" target="_blank" rel="noopener">@include('student.partials.icon', ['name' => 'send', 'size' => 18]) تواصل مع الإدارة</a>
        @endif
        <form method="POST" action="{{ route('student.logout') }}">
            @csrf
            <button class="btn btn-outline btn-block" type="submit">@include('student.partials.icon', ['name' => 'logout', 'size' => 18]) تسجيل الخروج</button>
        </form>
        <p class="center muted small">الحساب مرتبط بهذا الجهاز. لتغيير الجهاز تواصل مع الإدارة.</p>
    </div>
</main>
@endsection
