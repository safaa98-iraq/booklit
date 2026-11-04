@extends('student.layout')
@section('title', 'بحث')
@section('tab', 'home')
@section('content')
@include('student.partials.appbar', ['title' => 'بحث', 'back' => route('student.home')])
<main class="page">
    <form method="GET" action="{{ route('student.search') }}">
        <input class="search-input" type="search" name="q" value="{{ $q }}" placeholder="ابحث عن ملزمة أو موضوع أو مصطلح…" autofocus>
    </form>

    @if (mb_strlen($q) >= 2)
        @if ($booklets->isEmpty() && $topics->isEmpty())
            <div class="empty"><span class="ic">@include('student.partials.icon', ['name' => 'search', 'size' => 28])</span><h3>لا توجد نتائج</h3><p>جرّب كلمة أخرى أو مصطلحاً إنكليزياً.</p></div>
        @endif
        @if ($booklets->isNotEmpty())
            <div class="section"><div class="section-title"><h2>الملازم</h2></div>
                <div class="card list">
                    @foreach ($booklets as $b)
                        <a class="item" href="{{ $lockedSubjects[$b->subject_id] ?? false ? route('student.library.subject', $b->subject_id) : route('student.booklets.show', $b) }}">
                            <span class="ic">@include('student.partials.icon', ['name' => 'book', 'size' => 18])</span>
                            <span class="grow"><b>{{ $b->title }}</b><br><span class="muted small">{{ $b->subject->name }} · {{ $b->subject->stage->name }}</span></span>
                            @if ($lockedSubjects[$b->subject_id] ?? false) @include('student.partials.icon', ['name' => 'lock', 'size' => 16]) @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
        @if ($topics->isNotEmpty())
            <div class="section"><div class="section-title"><h2>المواضيع</h2></div>
                <div class="card list">
                    @foreach ($topics as $row)
                        @php($locked = $lockedSubjects[$row['topic']->booklet->subject_id] ?? false)
                        <a class="item" href="{{ $locked ? route('student.library.subject', $row['topic']->booklet->subject_id) : route('student.topics.show', [$row['topic'], 'hl' => $row['hl']]).($row['hl'] ? '#b-'.\Illuminate\Support\Str::before($row['hl'], ':') : '') }}">
                            <span class="ic">@include('student.partials.icon', ['name' => 'list', 'size' => 18])</span>
                            <span class="grow"><b>{{ $row['topic']->title }}</b><br>
                                <span class="muted small">{{ $row['topic']->booklet->title }}</span>
                                @if ($row['snippet'])<br><span class="small">{{ $row['snippet'] }}</span>@endif
                            </span>
                            @if ($locked) @include('student.partials.icon', ['name' => 'lock', 'size' => 16]) @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <p class="muted center" style="margin-top:30px">اكتب حرفين على الأقل للبحث.</p>
    @endif
</main>
@endsection
