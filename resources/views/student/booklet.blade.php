@extends('student.layout')
@section('title', $booklet->title)
@section('nav-title', $booklet->subject->name)
@section('back', route('student.home'))
@section('protected', '1')
@section('topbar-actions')
    <button class="icon-btn" type="button" data-open="toc-dialog" aria-label="المواضيع">@include('student.partials.icon', ['name' => 'menu'])</button>
@endsection
@section('content')
@include('student.partials.katex')

@if ($highlight)
    <div class="hl-banner">
        <div class="container">
            @include('student.partials.icon', ['name' => 'mark', 'size' => 20])
            <span>المقطع المظلل هو مصدر إجابة السؤال.</span>
            <span class="spacer" style="flex:1"></span>
            <a href="javascript:history.back()">رجوع للنتيجة</a>
        </div>
    </div>
@endif

<main class="container">
    <div class="reader">
        <aside class="toc-side">
            <div class="muted small" style="font-weight:700;margin-bottom:6px">المواضيع</div>
            <nav class="toc">
                @foreach ($booklet->topics as $i => $topic)
                    <a href="#t-{{ $topic->id }}"><span class="num">{{ $i + 1 }}</span>{{ $topic->title }}</a>
                @endforeach
            </nav>
        </aside>

        <article class="booklet-content">
            <header class="booklet-head">
                <div class="muted small">{{ $booklet->subject->stage->name }} · {{ $booklet->subject->name }}</div>
                <h1>{{ $booklet->title }}</h1>
                @if ($booklet->description)<p class="muted" style="margin:6px 0 0">{{ $booklet->description }}</p>@endif
            </header>
            @forelse ($booklet->topics as $topic)
                <h2 class="folder topic-title" id="t-{{ $topic->id }}">{{ $topic->title }}</h2>
                @foreach ($topic->blocks as $block)
                    @include('student.partials.block', [
                        'block' => $block,
                        'hl' => $highlight && $highlight['block'] === $block->uuid ? $highlight : null,
                    ])
                @endforeach
            @empty
                <p class="muted center">لا يوجد محتوى في هذه الملزمة بعد.</p>
            @endforelse
        </article>
    </div>
</main>

@unless ($highlight)
    <nav class="actions" aria-label="الأسئلة">
        <div class="inner">
            <button class="action a-blue" type="button" data-open="mcq-ministerial" @disabled($stats['ministerial'] === 0)>
                @include('student.partials.icon', ['name' => 'bank']) MCQ وزاري
            </button>
            <button class="action a-gray" type="button" data-open="mcq-teacher" @disabled($stats['teacher'] === 0)>
                @include('student.partials.icon', ['name' => 'school']) MCQ الأستاذ
            </button>
            <button class="action a-yellow" type="button" data-open="quiz-dialog" @disabled(array_sum($stats['quiz']) === 0)>
                @include('student.partials.icon', ['name' => 'timer']) وضع الاختبار
            </button>
            @php($fourthIcon = ['video' => 'video', 'info' => 'info'][$fourth['icon'] ?? ''] ?? 'star')
            @if (($fourth['enabled'] ?? false) && ($fourth['action'] ?? '') === 'url' && filled($fourth['value'] ?? null))
                <a class="action a-lavender" href="{{ $fourth['value'] }}" target="_blank" rel="noopener">@include('student.partials.icon', ['name' => $fourthIcon]) {{ $fourth['label'] }}</a>
            @else
                <button class="action a-lavender" type="button" @disabled(! ($fourth['enabled'] ?? false)) onclick="alert('هذه الميزة قادمة قريباً.')">
                    @include('student.partials.icon', ['name' => $fourthIcon]) {{ ($fourth['enabled'] ?? false) ? $fourth['label'] : 'قريباً' }}
                </button>
            @endif
        </div>
    </nav>
@endunless

{{-- Topics (mobile) --}}
<dialog id="toc-dialog">
    <div class="dialog-body" style="text-align:start">
        <h3>المواضيع</h3>
        <nav class="toc" style="margin-top:10px">
            @foreach ($booklet->topics as $i => $topic)
                <a href="#t-{{ $topic->id }}" data-close><span class="num">{{ $i + 1 }}</span>{{ $topic->title }}</a>
            @endforeach
        </nav>
    </div>
</dialog>

{{-- MCQ: solve or show solutions --}}
@foreach (['ministerial' => 'MCQ وزاري', 'teacher' => 'MCQ الأستاذ'] as $source => $label)
    <dialog id="mcq-{{ $source }}">
        <div class="dialog-body">
            <h3>{{ $label }}</h3>
            <div class="muted">{{ $stats[$source] }} سؤال</div>
            <div class="stack">
                <form method="POST" action="{{ route('student.attempts.store', $booklet) }}">
                    @csrf
                    <input type="hidden" name="mode" value="{{ $source }}">
                    <button class="btn btn-block" type="submit">@include('student.partials.icon', ['name' => 'pen', 'size' => 18]) ابدأ الحل</button>
                </form>
                <a class="btn btn-ghost btn-block" href="{{ route('student.booklets.review', [$booklet, $source]) }}">@include('student.partials.icon', ['name' => 'eye', 'size' => 18]) أظهر الحل</a>
            </div>
        </div>
    </dialog>
@endforeach

{{-- Quiz: pick difficulty; question count is set by the editor --}}
<dialog id="quiz-dialog">
    <div class="dialog-body">
        <h3>وضع الاختبار</h3>
        <div class="muted">اختر مستوى الصعوبة — {{ $booklet->quiz_question_count }} أسئلة عشوائية</div>
        <form class="stack" method="POST" action="{{ route('student.attempts.store', $booklet) }}">
            @csrf
            <input type="hidden" name="mode" value="quiz">
            @foreach (['easy' => 'سهل', 'medium' => 'متوسط', 'hard' => 'صعب'] as $d => $label)
                <button class="diff-btn" type="submit" name="difficulty" value="{{ $d }}" @disabled(($stats['quiz'][$d] ?? 0) === 0)>
                    <span class="pill pill-{{ $d }}">{{ $label }}</span>
                    <span class="muted small">{{ $stats['quiz'][$d] ?? 0 }} سؤال متاح</span>
                </button>
            @endforeach
        </form>
    </div>
</dialog>

@if ($highlight)
    @push('scripts')
        <script>document.getElementById('b-{{ $highlight['block'] }}')?.scrollIntoView({ block: 'center' });</script>
    @endpush
@endif
@endsection
