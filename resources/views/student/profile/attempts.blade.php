@extends('student.layout')
@section('title', 'نتائج الاختبارات')
@section('tab', 'profile')
@section('content')
@include('student.partials.appbar', ['title' => 'نتائج الاختبارات', 'back' => route('student.profile')])
<main class="page">
    @if ($attempts->isEmpty())
        <div class="card"><div class="empty"><span class="ic">@include('student.partials.icon', ['name' => 'clipboard', 'size' => 28])</span><h3>لم تحل أي اختبار بعد</h3><p>ابدأ اختباراً من أسفل أي موضوع في الملزمة.</p></div></div>
    @else
        <div class="card list">
            @foreach ($attempts as $a)
                <a class="item" href="{{ route('student.attempts.show', $a) }}">
                    @if ($a->isSubmitted())
                        <span class="score-badge" style="background:{{ $a->score >= 75 ? 'var(--green-soft)' : ($a->score >= 50 ? 'var(--amber-soft)' : 'var(--red-soft)') }};color:{{ $a->score >= 75 ? 'var(--green)' : ($a->score >= 50 ? 'var(--amber)' : 'var(--red)') }}">{{ round($a->score) }}%</span>
                    @else
                        <span class="score-badge" style="background:var(--violet-soft);color:var(--violet)">جارٍ</span>
                    @endif
                    <span class="grow"><b>{{ $a->mode->getLabel() }}</b>@if ($a->difficulty) <span class="pill pill-{{ $a->difficulty->value }}">{{ $a->difficulty->getLabel() }}</span>@endif<br>
                        <span class="muted small">{{ $a->topic?->title ?? $a->booklet?->title ?? $a->subject?->name }} · {{ ($a->submitted_at ?? $a->started_at)?->format('Y/m/d') }}</span></span>
                    <span class="muted small">{{ $a->isSubmitted() ? $a->correct_count.'/'.$a->total_questions : 'أكمل' }}</span>
                </a>
            @endforeach
        </div>
        <div style="margin-top:12px">{{ $attempts->links('pagination::simple-default') }}</div>
    @endif
</main>
@endsection
