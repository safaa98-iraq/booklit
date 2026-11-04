@extends('student.layout')
@section('title', 'شوهد مؤخراً')
@section('tab', 'profile')
@section('content')
@include('student.partials.appbar', ['title' => 'شوهد مؤخراً', 'back' => route('student.profile')])
<main class="page">
    @if ($items->isEmpty())
        <div class="card"><div class="empty"><span class="ic">@include('student.partials.icon', ['name' => 'history', 'size' => 28])</span><h3>لا يوجد شيء بعد</h3><p>المواضيع التي تقرؤها ستظهر هنا.</p></div></div>
    @else
        <div class="card list">
            @foreach ($items as $p)
                <a class="item" href="{{ route('student.topics.show', $p->topic) }}">
                    <span class="ic">@include('student.partials.icon', ['name' => 'list', 'size' => 18])</span>
                    <span class="grow"><b>{{ $p->topic->title }}</b><br><span class="muted small">{{ $p->booklet->title }} · {{ $p->last_read_at?->diffForHumans() }}</span></span>
                    <span class="muted small">{{ $p->completed_at ? 'مكتمل' : $p->scroll_percent.'%' }}</span>
                </a>
            @endforeach
        </div>
    @endif
</main>
@endsection
