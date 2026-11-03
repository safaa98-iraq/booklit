@extends('student.layout')
@section('title', $attempt->mode->getLabel())
@section('nav-title', $attempt->mode->getLabel())
@section('back', $attempt->booklet ? route('student.booklets.show', $attempt->booklet) : route('student.home'))
@section('protected', '1')
@section('content')
@php($letters = ['أ', 'ب', 'ج', 'د', 'هـ', 'و'])
<form id="solve" method="POST" action="{{ route('student.attempts.submit', $attempt) }}">
    @csrf
    <div class="solve-head">
        <div class="container narrow">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <strong>{{ $attempt->mode->getLabel() }}</strong>
                @if ($attempt->difficulty)<span class="pill pill-{{ $attempt->difficulty->value }}">{{ $attempt->difficulty->getLabel() }}</span>@endif
                <span style="flex:1"></span>
                <span class="muted small">أجبت <b id="answered">0</b> من {{ $attempt->answers->count() }}</span>
                <button class="btn btn-sm btn-yellow" type="submit">إرسال</button>
            </div>
            <div class="progress"><div id="bar"></div></div>
        </div>
    </div>

    <main class="container narrow" style="padding-bottom:32px">
        <p class="muted small">يمكنك تغيير أي إجابة قبل الضغط على «إرسال». يتم حفظ إجاباتك تلقائياً.</p>
        @foreach ($attempt->answers as $i => $answer)
            @php($question = $answer->question)
            @php($options = collect($answer->option_order)->map(fn ($id) => $question->options->firstWhere('id', $id))->filter()->concat($question->options->whereNotIn('id', $answer->option_order))->values())
            <section class="card question" id="q-{{ $question->id }}">
                <div class="card-body">
                    <div class="q-head">
                        <span class="q-num">{{ $i + 1 }}</span>
                        <span class="pill pill-{{ $question->difficulty->value }}">{{ $question->difficulty->getLabel() }}</span>
                    </div>
                    <div class="q-text">{{ $question->text }}</div>
                    @if ($question->image)
                        <img src="{{ \App\Support\Media::url($question->image) }}" alt="" style="max-width:100%;border-radius:12px;margin-bottom:8px">
                    @endif
                    @foreach ($options as $j => $option)
                        <label class="option">
                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" data-q="{{ $question->id }}" @checked($answer->selected_option_id === $option->id)>
                            <span class="letter">{{ $letters[$j] ?? $j + 1 }}</span>
                            <span>{{ $option->text }}</span>
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
        <button class="btn btn-block btn-yellow" type="submit" style="margin-top:10px">إرسال الإجابات</button>
    </main>
</form>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('solve');
    const total = {{ $attempt->answers->count() }};
    const token = document.querySelector('meta[name=csrf-token]').content;
    const answered = () => new Set([...form.querySelectorAll('input[type=radio]:checked')].map(i => i.dataset.q)).size;
    const refresh = () => {
        const n = answered();
        document.getElementById('answered').textContent = n;
        document.getElementById('bar').style.width = (total ? n / total * 100 : 0) + '%';
    };
    form.addEventListener('change', e => {
        if (e.target.type !== 'radio') return;
        refresh();
        fetch(@json(route('student.attempts.answer', $attempt)), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: JSON.stringify({ question_id: +e.target.dataset.q, option_id: +e.target.value }),
        }).catch(() => {});
    });
    let sending = false;
    form.addEventListener('submit', e => {
        if (sending) return;
        const left = total - answered();
        const msg = left === 0 ? 'هل تريد إرسال إجاباتك الآن؟' : `لم تجب على ${left} من ${total} سؤال، وستُحسب خاطئة. هل تريد الإرسال؟`;
        if (!confirm(msg)) { e.preventDefault(); return; }
        sending = true;
    });
    refresh();
})();
</script>
@endpush
@endsection
