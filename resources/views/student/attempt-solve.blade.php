@extends('student.layout')
@section('title', $attempt->mode->getLabel())
@section('protected', '1')
@section('content')
@php($letters = ['أ', 'ب', 'ج', 'د', 'هـ', 'و'])
@php($back = $attempt->topic ? route('student.topics.show', $attempt->topic) : route('student.home'))
<form id="solve" method="POST" action="{{ route('student.attempts.submit', $attempt) }}">
    @csrf
    <div class="solve-head">
        <div class="page" style="padding-bottom:0">
            <div class="row" style="margin-bottom:8px">
                <a class="icon-btn ghost" href="{{ $back }}" aria-label="رجوع">@include('student.partials.icon', ['name' => 'back'])</a>
                <div class="grow" style="min-width:0">
                    <b>{{ $attempt->mode->getLabel() }}</b> @if ($attempt->difficulty)<span class="pill pill-{{ $attempt->difficulty->value }}">{{ $attempt->difficulty->getLabel() }}</span>@endif
                    <div class="muted small ellipsis">{{ $attempt->topic?->title ?? $attempt->booklet?->title }} · أجبت <b id="answered">0</b> من {{ $attempt->answers->count() }}</div>
                </div>
                <button class="btn btn-sm" type="submit">إرسال</button>
            </div>
            <div class="bar"><i id="bar" style="width:0"></i></div>
        </div>
    </div>

    <main class="page">
        <p class="muted small">يمكنك تغيير أي إجابة قبل الضغط على «إرسال». يتم حفظ إجاباتك تلقائياً.</p>
        @foreach ($attempt->answers as $i => $answer)
            @php($question = $answer->question)
            @php($options = collect($answer->option_order)->map(fn ($id) => $question->options->firstWhere('id', $id))->filter()->concat($question->options->whereNotIn('id', $answer->option_order))->values())
            <section class="card question" id="q-{{ $question->id }}">
                <div class="card-body">
                    <div class="q-head"><span class="q-num">{{ $i + 1 }}</span><span class="pill pill-{{ $question->difficulty->value }}">{{ $question->difficulty->getLabel() }}</span></div>
                    <div class="q-text">{{ $question->text }}</div>
                    @if ($question->image)<img src="{{ \App\Support\Media::url($question->image) }}" alt="" style="max-width:100%;border-radius:12px;margin-bottom:8px">@endif
                    @foreach ($options as $j => $option)
                        <label class="option">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" data-q="{{ $question->id }}" @checked($answer->selected_option_id === $option->id)>
                            <span class="letter">{{ $letters[$j] ?? $j + 1 }}</span><span>{{ $option->text }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
        <button class="btn btn-grad btn-block" type="submit" style="margin-top:6px">إرسال الإجابات</button>
    </main>
</form>
@push('scripts')
<script>
(() => {
    const form = document.getElementById('solve'), total = {{ $attempt->answers->count() }};
    const answered = () => new Set([...form.querySelectorAll('input[type=radio]:checked')].map(i => i.dataset.q)).size;
    const refresh = () => { const n = answered(); document.getElementById('answered').textContent = n; document.getElementById('bar').style.width = (total ? n / total * 100 : 0) + '%'; };
    form.addEventListener('change', e => {
        if (e.target.type !== 'radio') return;
        refresh();
        Booklit.post(@json(route('student.attempts.answer', $attempt)), { question_id: +e.target.dataset.q, option_id: +e.target.value }).catch(() => {});
    });
    let sending = false;
    form.addEventListener('submit', e => {
        if (sending) return;
        const left = total - answered();
        if (!confirm(left === 0 ? 'هل تريد إرسال إجاباتك الآن؟' : `لم تجب على ${left} من ${total} سؤال، وستُحسب خاطئة. هل تريد الإرسال؟`)) { e.preventDefault(); return; }
        sending = true;
    });
    refresh();
})();
</script>
@endpush
@endsection
