@extends('student.layout')
@section('title', 'الرئيسية')
@section('tab', 'home')
@section('content')
<header class="appbar">
    <div class="inner">
        <span class="logo grow">BOOKLIT</span>
        <a class="icon-btn" href="{{ route('student.search') }}" aria-label="بحث">@include('student.partials.icon', ['name' => 'search'])</a>
        <a class="icon-btn" href="{{ route('student.notifications') }}" aria-label="الإشعارات">@include('student.partials.icon', ['name' => 'bell'])@if ($unread)<span class="dot"></span>@endif</a>
    </div>
</header>

<main class="page">
    <div class="greet">
        <div class="hello">مرحباً 👋</div>
        <h2>{{ $student->name }}</h2>
    </div>

    @if ($continue)
        @php($p = $continue['progress'])
        <section class="continue">
            <div class="label">تابع القراءة</div>
            <h3>{{ $p->booklet->title }}</h3>
            <div class="topic">{{ $p->topic->title }}</div>
            <div class="bar"><i style="width: {{ $continue['booklet']['percent'] }}%"></i></div>
            <div class="row between">
                <span class="small">{{ $continue['booklet']['percent'] }}% · {{ $continue['booklet']['completed'] }} من {{ $continue['booklet']['total'] }} مواضيع</span>
                <a class="btn btn-white btn-sm" href="{{ route('student.topics.show', $p->topic) }}">متابعة @include('student.partials.icon', ['name' => 'next', 'size' => 16])</a>
            </div>
        </section>
    @else
        <section class="continue">
            <div class="label">ابدأ من هنا</div>
            <h3>اختر مادة من المكتبة وابدأ القراءة</h3>
            <div class="row between" style="margin-top:12px">
                <span class="small">سيتم حفظ موضع قراءتك تلقائياً</span>
                <a class="btn btn-white btn-sm" href="{{ route('student.library') }}">المكتبة @include('student.partials.icon', ['name' => 'next', 'size' => 16])</a>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="section-title"><h2>المراحل</h2><a class="link small" href="{{ route('student.library') }}">عرض الكل</a></div>
        <div class="shortcuts">
            @foreach ($stages as $stage)
                <a class="shortcut" href="{{ route('student.library', ['stage' => $stage->id]) }}">
                    <span class="ic">@include('student.partials.icon', ['name' => 'school', 'size' => 18])</span>
                    <span>{{ $stage->name }}<small>{{ $stage->subjects_count }} مواد @if ($stage->is_ministerial)· اشتراك @endif</small></span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="section">
        <div class="section-title"><h2>ملازم بدأت بدراستها</h2></div>
        @if ($inProgress->isEmpty())
            <div class="card"><div class="empty"><span class="ic">@include('student.partials.icon', ['name' => 'book', 'size' => 28])</span><h3>لم تبدأ أي ملزمة بعد</h3><p>افتح المكتبة واختر ملزمة لتظهر هنا مع نسبة تقدمك.</p></div></div>
        @else
            <div class="card list">
                @foreach ($inProgress as $row)
                    <a class="item" href="{{ route('student.booklets.show', $row['booklet']) }}">
                        <span class="ic">@include('student.partials.icon', ['name' => 'book', 'size' => 18])</span>
                        <span class="grow">
                            <b class="ellipsis" style="display:block">{{ $row['booklet']->title }}</b>
                            <span class="muted small">{{ $row['booklet']->subject->name }} · {{ $row['booklet']->subject->stage->name }}</span>
                            <span class="bar" style="display:block;margin-top:6px"><i style="width: {{ $row['percent'] }}%"></i></span>
                        </span>
                        <span class="pct">{{ $row['percent'] }}%</span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
</main>
@endsection
