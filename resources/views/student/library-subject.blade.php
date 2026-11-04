@extends('student.layout')
@section('title', $subject->name)
@section('tab', 'library')
@section('content')
@include('student.partials.appbar', ['title' => $subject->name, 'back' => route('student.library', ['stage' => $subject->stage_id]), 'wide' => true])
<main class="page wide">
    <p class="muted small" style="margin:0 0 12px">{{ $subject->stage->name }} · {{ $booklets->count() }} ملزمة</p>

    @if ($booklets->isEmpty())
        <div class="card"><div class="empty"><span class="ic">@include('student.partials.icon', ['name' => 'book', 'size' => 28])</span><h3>لا توجد ملازم منشورة بعد</h3></div></div>
    @else
        <div class="split">
            {{-- Booklets (on phones: shown first) --}}
            <section class="pane {{ $showTopics ? 'hidden' : '' }}">
                <div class="card card-body" style="padding:8px">
                    @foreach ($booklets as $b)
                        <a class="booklet-item {{ $selected && $selected->id === $b->id ? 'active' : '' }}" href="{{ route('student.library.subject', [$subject, 'booklet' => $b->id]) }}">
                            <span class="ic">@include('student.partials.icon', ['name' => 'book', 'size' => 18])</span>
                            <span class="grow">
                                <b class="ellipsis" style="display:block">{{ $b->title }}</b>
                                <span class="muted small">{{ $b->topics_count }} مواضيع · {{ $progress[$b->id]['percent'] }}%</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Topics of the selected booklet --}}
            @if ($selected)
                <section class="pane {{ $showTopics ? '' : 'hidden' }}">
                    <a class="back-to-booklets link small row" style="margin-bottom:10px" href="{{ route('student.library.subject', $subject) }}">@include('student.partials.icon', ['name' => 'back', 'size' => 16]) كل الملازم</a>
                    <div class="card">
                        <div class="card-body row" style="border-bottom:1px solid var(--line)">
                            <span class="grow">
                                <b style="font-size:1.05rem">{{ $selected->title }}</b><br>
                                <span class="muted small">{{ $progress[$selected->id]['completed'] }} من {{ $progress[$selected->id]['total'] }} مكتملة</span>
                            </span>
                            <button class="fav-btn {{ in_array($selected->id, $favorites) ? 'on' : '' }}" type="button" data-fav="booklet" data-id="{{ $selected->id }}" aria-label="إضافة إلى المفضلة">@include('student.partials.icon', ['name' => 'heart', 'size' => 18])</button>
                            <a class="btn btn-sm" href="{{ route('student.booklets.show', $selected) }}">{{ $progress[$selected->id]['completed'] ? 'متابعة' : 'ابدأ القراءة' }}</a>
                        </div>
                        @forelse ($selected->topics as $i => $topic)
                            @php($row = $done[$topic->id] ?? null)
                            <a class="topic-item" href="{{ route('student.topics.show', $topic) }}">
                                <span class="num {{ $row?->completed_at ? 'done' : '' }}">@if ($row?->completed_at) @include('student.partials.icon', ['name' => 'check', 'size' => 14]) @else {{ $i + 1 }} @endif</span>
                                <span class="grow">{{ $topic->title }}</span>
                                @if ($row && ! $row->completed_at)<span class="muted small">{{ $row->scroll_percent }}%</span>@endif
                                <span class="chev" style="color:#B5B4C9">@include('student.partials.icon', ['name' => 'chev', 'size' => 16])</span>
                            </a>
                        @empty
                            <div class="empty">لا توجد مواضيع بعد.</div>
                        @endforelse
                    </div>
                </section>
            @endif
        </div>
    @endif
</main>
@endsection
