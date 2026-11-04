@extends('student.layout')
@section('title', 'المكتبة')
@section('tab', 'library')
@section('content')
@include('student.partials.appbar', ['title' => 'المكتبة'])
<main class="page">
    @if ($stages->isEmpty())
        <div class="empty"><h3>لا توجد مراحل بعد</h3></div>
    @else
        <nav class="chips" aria-label="المراحل">
            @foreach ($stages as $s)
                <a class="chip {{ $s->id === $stage->id ? 'active' : '' }}" href="{{ route('student.library', ['stage' => $s->id]) }}">{{ $s->name }}</a>
            @endforeach
        </nav>

        <div class="section">
            <div class="section-title"><h2>مواد {{ $stage->name }}</h2><span class="muted small">{{ $stage->subjects->count() }} مواد</span></div>
            <div class="subjects">
                @forelse ($stage->subjects as $subject)
                    @if ($locked[$subject->id] ?? false)
                        <button type="button" class="subject-card" data-open="locked-dialog" aria-label="{{ $subject->name }} — مقفلة">
                            <span class="lock">@include('student.partials.icon', ['name' => 'lock', 'size' => 14])</span>
                            <span class="ic">@include('student.partials.icon', ['name' => 'book'])</span>
                            <span class="name">{{ $subject->name }}</span>
                            <span class="meta">{{ $subject->booklets_count }} ملزمة · للمشتركين</span>
                        </button>
                    @else
                        <a class="subject-card" href="{{ route('student.library.subject', $subject) }}">
                            <span class="ic">@include('student.partials.icon', ['name' => 'book'])</span>
                            <span class="name">{{ $subject->name }}</span>
                            <span class="meta">{{ $subject->booklets_count }} ملزمة · {{ $subject->requiresSubscription() ? 'مفعّلة لك' : 'مجانية' }}</span>
                        </a>
                    @endif
                @empty
                    <p class="muted">لا توجد مواد في هذه المرحلة بعد.</p>
                @endforelse
            </div>
        </div>
    @endif
</main>
@include('student.partials.locked-dialog')
@if (session('locked'))
    @push('scripts')<script>document.getElementById('locked-dialog').showModal();</script>@endpush
@endif
@endsection
