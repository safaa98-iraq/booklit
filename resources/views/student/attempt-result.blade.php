@extends('student.layout')
@section('title', 'النتيجة')
@section('nav-title', 'النتيجة')
@section('back', $attempt->booklet ? route('student.booklets.show', $attempt->booklet) : route('student.home'))
@section('content')
@php
    $score = (float) $attempt->score;
    $col = $score >= 75 ? 'var(--green)' : ($score >= 50 ? 'var(--orange)' : 'var(--red)');
    $message = $score >= 90 ? 'ممتاز! أداء رائع 🌟' : ($score >= 75 ? 'جيد جداً، استمر 👏' : ($score >= 50 ? 'جيد، راجع الأسئلة الخاطئة' : 'تحتاج إلى مراجعة الملزمة'));
@endphp
<main class="container narrow" style="padding:16px 16px 40px">
    <div class="result-top">
        <div class="folder f-lavender">
            <span class="kicker">{{ $attempt->mode->getLabel() }}@if ($attempt->difficulty) — {{ $attempt->difficulty->getLabel() }}@endif · {{ $attempt->booklet?->title }}</span>
            <span><span class="big">{{ round($score) }}%</span><br><span class="title">{{ $message }}</span></span>
        </div>
        <div class="folder f-blue">
            <span class="kicker">إجابات صحيحة</span>
            <span class="big">{{ $attempt->correct_count }}<small style="font-size:1.2rem;opacity:.7"> / {{ $attempt->total_questions }}</small></span>
        </div>
        <div class="folder f-gray">
            <span class="kicker">تحتاج مراجعة</span>
            <span class="corner">@include('student.partials.icon', ['name' => 'arrow-up', 'size' => 24])</span>
            <span class="big">{{ $attempt->total_questions - $attempt->correct_count }}</span>
        </div>
    </div>

    <div class="toolbar">
        <button class="chip active" type="button" id="show-all">كل الأسئلة</button>
        <button class="chip" type="button" id="show-wrong">الخاطئة فقط ({{ $attempt->total_questions - $attempt->correct_count }})</button>
        <span style="flex:1"></span>
        @if ($attempt->booklet)
            <form method="POST" action="{{ route('student.attempts.store', $attempt->booklet) }}">
                @csrf
                <input type="hidden" name="mode" value="{{ $attempt->mode->value }}">
                @if ($attempt->difficulty)<input type="hidden" name="difficulty" value="{{ $attempt->difficulty->value }}">@endif
                <button class="btn btn-sm" type="submit">إعادة المحاولة</button>
            </form>
        @endif
    </div>

    <div id="solutions">
        @foreach ($attempt->answers as $i => $answer)
            @php($q = $answer->question)
            @include('student.partials.solution', [
                'number' => $i + 1,
                'question' => $q,
                'options' => collect($answer->option_order)->map(fn ($id) => $q->options->firstWhere('id', $id))->filter(),
                'chosen' => $answer->selected_option_id,
                'isCorrect' => (bool) $answer->is_correct,
            ])
        @endforeach
    </div>
</main>
@push('scripts')
<script>
    const all = document.getElementById('show-all'), wrong = document.getElementById('show-wrong');
    const filter = onlyWrong => {
        document.querySelectorAll('#solutions .question').forEach(q => q.classList.toggle('hidden', onlyWrong && q.dataset.correct === '1'));
        all.classList.toggle('active', !onlyWrong); wrong.classList.toggle('active', onlyWrong);
    };
    all.onclick = () => filter(false); wrong.onclick = () => filter(true);
</script>
@endpush
@endsection
