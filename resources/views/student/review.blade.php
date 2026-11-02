@extends('student.layout')
@section('title', $mode->getLabel().' — الحلول')
@section('back', route('student.booklets.show', $booklet))
@section('protected', '1')
@section('content')
<main class="container narrow" style="padding:16px 16px 40px">
    <p class="muted">{{ $questions->count() }} سؤال مع الإجابة الصحيحة والشرح.</p>
    @forelse ($questions as $i => $question)
        @include('student.partials.solution', [
            'number' => $i + 1,
            'question' => $question,
            'options' => $question->options->shuffle(),
            'chosen' => null,
            'isCorrect' => null,
            'review' => true,
        ])
    @empty
        <div class="card"><div class="card-body center muted">لا توجد أسئلة.</div></div>
    @endforelse
</main>
@endsection
