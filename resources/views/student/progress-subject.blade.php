@extends('student.layout')
@section('title', $subject->name)
@section('tab', 'progress')
@section('content')
@include('student.partials.appbar', ['title' => $subject->name, 'back' => route('student.progress')])
<main class="page">
    <p class="muted small" style="margin-top:0">{{ $subject->stage->name }}</p>
    @foreach ($booklets as $row)
        <div class="card" style="margin-bottom:14px">
            <div class="card-body">
                <div class="row between"><b>{{ $row['booklet']->title }}</b><span class="pct">{{ $row['percent'] }}%</span></div>
                <div class="muted small">{{ $row['completed'] }} من {{ $row['total'] }} موضوع مكتمل</div>
                <div class="bar" style="margin-top:8px"><i style="width: {{ $row['percent'] }}%"></i></div>
            </div>
            <div class="list" style="border-top:1px solid var(--line)">
                @foreach ($row['booklet']->topics as $i => $topic)
                    @php($p = $rows[$topic->id] ?? null)
                    <a class="topic-item" href="{{ route('student.topics.show', $topic) }}">
                        <span class="num {{ $p?->completed_at ? 'done' : '' }}">@if ($p?->completed_at) @include('student.partials.icon', ['name' => 'check', 'size' => 14]) @else {{ $i + 1 }} @endif</span>
                        <span class="grow">{{ $topic->title }}</span>
                        <span class="muted small">{{ $p?->completed_at ? 'مكتمل' : ($p ? $p->scroll_percent.'%' : 'لم يبدأ') }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</main>
@endsection
