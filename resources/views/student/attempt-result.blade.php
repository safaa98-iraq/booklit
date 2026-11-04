@extends('student.layout')
@section('title', 'النتيجة')
@section('content')
@php
    $score = (float) $attempt->score;
    $message = $score >= 90 ? 'ممتاز! أداء رائع' : ($score >= 75 ? 'جيد جداً، استمر' : ($score >= 50 ? 'جيد، راجع الأسئلة الخاطئة' : 'تحتاج إلى مراجعة الموضوع'));
    $back = $attempt->topic ? route('student.topics.show', $attempt->topic) : route('student.profile.attempts');
@endphp
@include('student.partials.appbar', ['title' => 'النتيجة', 'back' => $back])
<main class="page">
    <div class="score-card">
        <div class="ring" style="--p: {{ $score }}"><span>{{ round($score) }}%</span></div>
        <div>
            <div class="small" style="opacity:.85">{{ $attempt->mode->getLabel() }}@if ($attempt->difficulty) — {{ $attempt->difficulty->getLabel() }}@endif</div>
            <b style="font-size:1.2rem">{{ $message }}</b>
            <div class="small" style="opacity:.9">{{ $attempt->correct_count }} إجابة صحيحة من {{ $attempt->total_questions }} · {{ $attempt->topic?->title ?? $attempt->booklet?->title }}</div>
        </div>
    </div>

    <div class="toolbar">
        <button class="chip active" type="button" id="show-all">كل الأسئلة</button>
        <button class="chip" type="button" id="show-wrong">الخاطئة فقط ({{ $attempt->total_questions - $attempt->correct_count }})</button>
        <span class="grow"></span>
        @if ($attempt->topic)
            <form method="POST" action="{{ route('student.attempts.store', $attempt->topic) }}">
                @csrf <input type="hidden" name="mode" value="{{ $attempt->mode->value }}">
                @if ($attempt->difficulty)<input type="hidden" name="difficulty" value="{{ $attempt->difficulty->value }}">@endif
                <button class="btn btn-light btn-sm" type="submit">إعادة المحاولة</button>
            </form>
        @endif
    </div>

    <div id="solutions">
        @foreach ($attempt->answers as $i => $answer)
            @php($q = $answer->question)
            @include('student.partials.solution', [
                'number' => $i + 1, 'question' => $q,
                'options' => collect($answer->option_order)->map(fn ($id) => $q->options->firstWhere('id', $id))->filter(),
                'chosen' => $answer->selected_option_id, 'isCorrect' => (bool) $answer->is_correct,
            ])
        @endforeach
    </div>
</main>
@push('scripts')
<script>
    const all = document.getElementById('show-all'), wrong = document.getElementById('show-wrong');
    const filter = only => { document.querySelectorAll('#solutions .question').forEach(q => q.classList.toggle('hidden', only && q.dataset.correct === '1')); all.classList.toggle('active', !only); wrong.classList.toggle('active', only); };
    all.onclick = () => filter(false); wrong.onclick = () => filter(true);
</script>
@endpush
@endsection
