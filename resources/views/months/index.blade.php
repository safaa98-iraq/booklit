@extends('layout')
@section('title', 'سنة '.$year)

@section('content')
    <section class="month-head">
        <a class="month-head__nav" href="{{ route('months.index', ['year' => $year - 1]) }}" aria-label="السنة السابقة">→</a>
        <div class="month-head__title">
            <span class="month-head__wafu" lang="ja" aria-hidden="true">年</span>
            <h1>سنة {{ $year }}</h1>
        </div>
        <a class="month-head__nav" href="{{ route('months.index', ['year' => $year + 1]) }}" aria-label="السنة التالية">←</a>
    </section>

    <section class="year-sum card">
        <div><span class="muted">أنفقت هذه السنة</span><strong>{{ money($totalSpent) }}</strong></div>
        <div><span class="muted">ادّخرت (في الأشهر المخطَّطة)</span><strong @class(['is-over' => $totalSaved < 0])>{{ money($totalSaved) }}</strong></div>
    </section>

    <section class="year-grid">
        @foreach ($months as $m)
            <a href="{{ route('months.show', $m->key) }}" @class(['tile', 'is-current' => $m->is_current, 'is-empty' => ! $m->has_plan && ! $m->spent])>
                <span class="tile__wafu" lang="ja">{{ $m->wafu }}</span>
                <strong class="tile__name">{{ $m->name }}</strong>
                @if ($m->has_plan || $m->spent)
                    <span class="tile__line"><span>أنفقت</span><b>{{ money($m->spent, false) }}</b></span>
                    @if (! is_null($m->saved))
                        <span class="tile__line"><span>ادّخرت</span><b @class(['is-over' => $m->saved < 0])>{{ money($m->saved, false) }}</b></span>
                    @endif
                @else
                    <span class="tile__line muted">صفحة فارغة</span>
                @endif
            </a>
        @endforeach
    </section>
@endsection
