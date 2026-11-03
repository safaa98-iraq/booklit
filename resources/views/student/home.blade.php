@extends('student.layout')
@section('title', 'الرئيسية')
@section('content')
@php($palette = ['f-blue', 'f-gray', 'f-yellow', 'f-lavender', 'f-coral', 'f-mint'])
<main class="container">
    <section class="hero" style="padding-bottom:8px">
        <div>
            <h1>أهلاً {{ \Illuminate\Support\Str::before($student->name, ' ') }}،<br>واصل رحلتك <span class="faces"><span style="background:var(--lavender)">🦷</span><span style="background:var(--yellow)">📘</span></span></h1>
            <div class="hero-actions">
                @if ($lastBooklet)
                    <a class="cta" href="{{ route('student.booklets.show', $lastBooklet) }}">تابع: {{ \Illuminate\Support\Str::limit($lastBooklet->title, 26) }} <span class="arrow">@include('student.partials.icon', ['name' => 'arrow', 'size' => 20])</span></a>
                @else
                    <a class="cta" href="#subjects">اختر مادة وابدأ <span class="arrow">@include('student.partials.icon', ['name' => 'arrow', 'size' => 20])</span></a>
                @endif
                <a class="play" href="{{ route('student.account') }}"><i>▶</i><span>نتائجي<br>ومحاولاتي</span></a>
            </div>
        </div>
        <div class="cards3" style="padding:0">
            <div class="folder f-blue">
                <span class="kicker">محاولاتك المكتملة</span>
                <span class="big">{{ $summary['attempts'] }}</span>
            </div>
            <div class="folder f-gray">
                <span class="kicker">متوسط درجاتك</span>
                <span class="big">{{ $summary['average'] }}<small style="font-size:1.4rem">%</small></span>
            </div>
            <div class="folder f-yellow">
                <span class="kicker">اشتراكات فعّالة</span>
                <span class="corner">@include('student.partials.icon', ['name' => 'arrow-up', 'size' => 24])</span>
                <span class="big">{{ $summary['subscriptions'] }}</span>
            </div>
        </div>
    </section>

    @if ($stages->isEmpty())
        <p class="muted">لا توجد مراحل متاحة حالياً.</p>
    @else
        <div class="section-head" id="subjects">
            <h2>{{ $stage->name }}</h2>
            <nav class="pills" aria-label="المراحل">
                @foreach ($stages as $s)
                    <a class="chip {{ $s->id === $stage->id ? 'active' : '' }}" href="{{ route('student.home', ['stage' => $s->id]) }}#subjects">
                        @if ($s->is_ministerial) @include('student.partials.icon', ['name' => 'badge', 'size' => 15]) @endif{{ $s->name }}
                    </a>
                @endforeach
            </nav>
        </div>

        <section class="grid">
            @forelse ($stage->subjects as $i => $subject)
                @php($isLocked = $locked[$subject->id] ?? false)
                @if ($isLocked)
                    <button type="button" class="folder subject locked" data-open="locked-dialog">
                        <span class="corner">@include('student.partials.icon', ['name' => 'lock', 'size' => 22])</span>
                        <span class="kicker">مادة وزارية</span>
                        <span><span class="title">{{ $subject->name }}</span><br><span class="state">للمشتركين فقط</span></span>
                    </button>
                @else
                    <a class="folder subject {{ $palette[$i % count($palette)] }}" href="{{ route('student.subjects.show', $subject) }}">
                        <span class="corner">@include('student.partials.icon', ['name' => 'arrow-up', 'size' => 24])</span>
                        <span class="ico">@include('student.partials.icon', ['name' => 'book', 'size' => 20])</span>
                        <span>
                            <span class="title">{{ $subject->name }}</span><br>
                            <span class="state">
                                @if ($subject->requiresSubscription()) @include('student.partials.icon', ['name' => 'check', 'size' => 14]) مفعّلة لك @else مجانية @endif
                            </span>
                        </span>
                    </a>
                @endif
            @empty
                <p class="muted">لا توجد مواد في هذه المرحلة بعد.</p>
            @endforelse
        </section>
    @endif
</main>

@include('student.partials.locked-dialog')
@if (session('locked'))
    @push('scripts')<script>document.getElementById('locked-dialog').showModal();</script>@endpush
@endif
@endsection
