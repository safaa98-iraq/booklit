@extends('student.layout')
@section('title', 'الرئيسية')
@section('content')
<main class="container">
    <div class="greeting">أهلاً {{ \Illuminate\Support\Str::before($student->name, ' ') }} 👋</div>

    @if ($stages->isEmpty())
        <p class="muted">لا توجد مراحل متاحة حالياً.</p>
    @else
        <nav class="stages" aria-label="المراحل">
            @foreach ($stages as $s)
                <a class="chip {{ $s->id === $stage->id ? 'active' : '' }}" href="{{ route('student.home', ['stage' => $s->id]) }}">
                    @if ($s->is_ministerial) @include('student.partials.icon', ['name' => 'badge', 'size' => 16]) @endif
                    {{ $s->name }}
                </a>
            @endforeach
        </nav>

        <section class="grid">
            @forelse ($stage->subjects as $subject)
                @php($isLocked = $locked[$subject->id] ?? false)
                @if ($isLocked)
                    <button type="button" class="subject locked" style="--c: {{ $subject->color ?: '#0d9488' }}" data-open="locked-dialog">
                        <span class="badge">@include('student.partials.icon', ['name' => 'lock'])</span>
                        <span class="ico">@include('student.partials.icon', ['name' => 'book'])</span>
                        <span><span class="name">{{ $subject->name }}</span><br><span class="state">للمشتركين فقط</span></span>
                    </button>
                @else
                    <a class="subject" style="--c: {{ $subject->color ?: '#0d9488' }}" href="{{ route('student.subjects.show', $subject) }}">
                        @if ($subject->requiresSubscription())
                            <span class="badge" style="color:var(--green)">@include('student.partials.icon', ['name' => 'check'])</span>
                        @endif
                        <span class="ico">@include('student.partials.icon', ['name' => 'book'])</span>
                        <span>
                            <span class="name">{{ $subject->name }}</span><br>
                            <span class="state {{ $subject->requiresSubscription() ? 'ok' : '' }}">{{ $subject->requiresSubscription() ? 'مفعّلة لك' : 'مجانية' }}</span>
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
