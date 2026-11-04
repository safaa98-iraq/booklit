@extends('student.layout')
@section('title', 'التقدم')
@section('tab', 'progress')
@section('content')
@include('student.partials.appbar', ['title' => 'التقدم الدراسي'])
<main class="page">
    <div class="card card-body overall">
        <div class="ring" style="--p: {{ $overall['percent'] }}"><span>{{ $overall['percent'] }}%</span></div>
        <div class="grow">
            <b style="font-size:1.1rem">التقدم الإجمالي</b>
            <div class="muted small">{{ $overall['completed'] }} من {{ $overall['total'] }} موضوع مكتمل</div>
            <div class="muted small">{{ $overall['booklets_completed'] }} ملزمة مكتملة · {{ \App\Services\ProgressService::formatDuration($stats['seconds']) }} قراءة</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title"><h2>حسب المادة</h2></div>
        @if ($subjects->isEmpty())
            <div class="card"><div class="empty"><span class="ic">@include('student.partials.icon', ['name' => 'chart', 'size' => 28])</span><h3>لا يوجد تقدم بعد</h3><p>ابدأ قراءة أي ملزمة وسيظهر تقدمك هنا.</p><a class="btn btn-light btn-sm" href="{{ route('student.library') }}">المكتبة</a></div></div>
        @else
            <div class="card list">
                @foreach ($subjects as $row)
                    <a class="item" href="{{ route('student.progress.subject', $row['subject']) }}">
                        <span class="ic">@include('student.partials.icon', ['name' => 'book', 'size' => 18])</span>
                        <span class="grow">
                            <span class="row between"><b>{{ $row['subject']->name }}</b><span class="pct">{{ $row['percent'] }}%</span></span>
                            <span class="muted small">{{ $row['subject']->stage->name }} · {{ $row['completed'] }} من {{ $row['total'] }} موضوع</span>
                            <span class="bar" style="display:block;margin-top:6px"><i style="width: {{ $row['percent'] }}%"></i></span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</main>
@endsection
