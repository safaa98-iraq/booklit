@extends('student.layout')
@section('title', $mode->getLabel().' — الحلول')
@section('protected', '1')
@section('content')
@include('student.partials.appbar', ['title' => $mode->getLabel().' — الحلول', 'back' => route('student.topics.show', $topic)])
<main class="page">
    <p class="muted small">{{ $topic->title }} · {{ $questions->count() }} سؤال مع الإجابة الصحيحة والشرح.</p>
    @forelse ($questions as $i => $question)
        @include('student.partials.solution', ['number' => $i + 1, 'question' => $question, 'options' => $question->options->shuffle(), 'chosen' => null, 'isCorrect' => null, 'review' => true])
    @empty
        <div class="card"><div class="empty">لا توجد أسئلة في هذا الموضوع.</div></div>
    @endforelse
</main>
@endsection
