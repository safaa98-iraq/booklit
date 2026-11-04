{{-- A question with its solution. $chosen = student's option id (null in review mode), $isCorrect = bool|null. --}}
@php
    $correct = $question->options->firstWhere('is_correct', true);
    $review = $review ?? false;
    $anchor = $question->anchors->first();
@endphp
<div class="card question" data-correct="{{ $review ? '' : ($isCorrect ? '1' : '0') }}">
    <div class="card-body">
        <div class="q-head">
            @if ($review)
                <span class="q-num">{{ $number }}</span>
            @else
                <span class="status-dot {{ $isCorrect ? 'ok' : 'bad' }}">@include('student.partials.icon', ['name' => $isCorrect ? 'check' : 'x', 'size' => 16])</span>
            @endif
            <span class="pill pill-{{ $question->difficulty->value }}">{{ $question->difficulty->getLabel() }}</span>
            <span style="flex:1"></span>
            @if ($question->source_reference)<span class="muted small">{{ $question->source_reference }}</span>@endif
        </div>
        <div class="q-text">{{ $question->text }}</div>
        @foreach ($options as $option)
            @php($cls = $option->id === $correct?->id ? 'correct' : (! $review && $option->id === $chosen ? 'wrong' : ''))
            <div class="opt {{ $cls }}">
                @if ($cls === 'correct') <span style="color:var(--green)">@include('student.partials.icon', ['name' => 'check', 'size' => 18])</span>
                @elseif ($cls === 'wrong') <span style="color:var(--red)">@include('student.partials.icon', ['name' => 'x', 'size' => 18])</span>
                @else <span style="width:18px"></span> @endif
                <span>{{ $option->text }}</span>
                @if (! $review && $option->id === $chosen)<span class="tag">إجابتك</span>@endif
            </div>
        @endforeach
        @if (! $review && $chosen === null)
            <div class="small" style="color:var(--amber);margin-top:6px">لم تتم الإجابة</div>
        @endif
        @if ($question->explanation)
            <div class="explain"><strong>الشرح: </strong>{{ $question->explanation }}</div>
        @endif
        @if ($anchor && $anchor->block)
            <div style="text-align:end;margin-top:8px">
                <a class="link" href="{{ route('student.topics.show', [$anchor->block->topic_id, 'hl' => $anchor->block->uuid.':'.$anchor->start_offset.':'.$anchor->end_offset]) }}#b-{{ $anchor->block->uuid }}">
                    @include('student.partials.icon', ['name' => 'highlight', 'size' => 16]) اعرض في الملزمة
                </a>
            </div>
        @endif
    </div>
</div>
