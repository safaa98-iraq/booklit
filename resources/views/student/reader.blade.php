@extends('student.layout')
@section('title', $topic->title)
@section('protected', '1')
@section('content')
@include('student.partials.katex')
@php
    $count = $topics->count();
    $ranges = function ($block) use ($anchor, $highlights) {
        $r = [];
        if ($anchor && $anchor['block'] === $block->uuid) {
            $r[] = ['start' => $anchor['start'], 'end' => $anchor['end'], 'class' => 'anchor'];
        }
        foreach ($highlights[$block->id] ?? [] as $h) {
            $r[] = ['start' => $h->start_offset, 'end' => $h->end_offset, 'class' => 'user', 'id' => $h->id];
        }
        return $r;
    };
@endphp

{{-- Top bar: hidden while reading (scrolling down), back on scroll up --}}
<header class="reader-top" id="reader-top">
    <div class="inner">
        <a class="icon-btn ghost" href="{{ route('student.library.subject', [$booklet->subject_id, 'booklet' => $booklet->id]) }}" aria-label="رجوع">@include('student.partials.icon', ['name' => 'back'])</a>
        <div class="grow" style="min-width:0">
            <div class="t ellipsis">{{ $topic->title }}</div>
            <div class="s ellipsis">{{ $booklet->title }}</div>
        </div>
        <button class="icon-btn ghost" type="button" data-open="tools" aria-label="أدوات">@include('student.partials.icon', ['name' => 'tools'])</button>
    </div>
    <div class="reader-progress"><i id="read-bar"></i></div>
</header>

<main class="reader">
    <div class="crumb">{{ $booklet->subject->name }} · الموضوع {{ $index + 1 }} من {{ $count }}</div>
    <h1 class="topic">{{ $topic->title }}</h1>

    @if ($anchor)
        <div class="hl-note">@include('student.partials.icon', ['name' => 'highlight', 'size' => 18]) المقطع المظلل هو مصدر إجابة السؤال.</div>
    @endif

    <article class="content" id="content" style="--fs: {{ $fontSize }}px">
        @forelse ($topic->blocks as $block)
            @include('student.partials.block', ['block' => $block, 'ranges' => $ranges($block)])
        @empty
            <p class="muted">لا يوجد محتوى في هذا الموضوع بعد.</p>
        @endforelse
    </article>

    {{-- Quiz buttons: below the content, scoped to this topic --}}
    <section class="quiz-tools">
        <h3>اختبر نفسك في هذا الموضوع</h3>
        <div class="quiz-grid">
            <button class="qbtn" type="button" data-open="mcq-ministerial" @disabled($stats['ministerial'] === 0)>
                <span class="ic">@include('student.partials.icon', ['name' => 'bank', 'size' => 18])</span>MCQ وزاري<small>{{ $stats['ministerial'] }} سؤال</small>
            </button>
            <button class="qbtn" type="button" data-open="mcq-teacher" @disabled($stats['teacher'] === 0)>
                <span class="ic">@include('student.partials.icon', ['name' => 'school', 'size' => 18])</span>MCQ الأستاذ<small>{{ $stats['teacher'] }} سؤال</small>
            </button>
            <button class="qbtn" type="button" data-open="tools">
                <span class="ic">@include('student.partials.icon', ['name' => 'tools', 'size' => 18])</span>أدوات<small>المحتويات، البحث، الخط…</small>
            </button>
            <button class="qbtn primary" type="button" data-open="quiz" @disabled(array_sum($stats['quiz']) === 0)>
                <span class="ic">@include('student.partials.icon', ['name' => 'timer', 'size' => 18])</span>وضع الاختبار<small>اختر الصعوبة</small>
            </button>
            @if (($fourth['enabled'] ?? false) && ($fourth['action'] ?? '') === 'url' && filled($fourth['value'] ?? null))
                <a class="qbtn" href="{{ $fourth['value'] }}" target="_blank" rel="noopener"><span class="ic">@include('student.partials.icon', ['name' => 'sparkle', 'size' => 18])</span>{{ $fourth['label'] }}<small>&nbsp;</small></a>
            @else
                <button class="qbtn" type="button" data-open="soon"><span class="ic">@include('student.partials.icon', ['name' => 'sparkle', 'size' => 18])</span>{{ ($fourth['enabled'] ?? false) ? $fourth['label'] : 'قريباً' }}<small>ميزة جديدة</small></button>
            @endif
        </div>

        <nav class="pager">
            @if ($prev)
                <a href="{{ route('student.topics.show', $prev) }}"><small>الموضوع السابق</small><b class="ellipsis">{{ $prev->title }}</b></a>
            @else <span></span> @endif
            @if ($next)
                <a class="next" href="{{ route('student.topics.show', $next) }}"><small>الموضوع التالي</small><b class="ellipsis">{{ $next->title }}</b></a>
            @endif
        </nav>
    </section>
</main>

{{-- Small bottom bar --}}
<nav class="minibar" aria-label="أدوات القراءة">
    <div class="inner">
        <button type="button" data-open="toc" aria-label="المحتويات">@include('student.partials.icon', ['name' => 'list'])</button>
        <a class="{{ $prev ? '' : 'disabled' }}" href="{{ $prev ? route('student.topics.show', $prev) : '#' }}" aria-label="السابق">@include('student.partials.icon', ['name' => 'back'])</a>
        <span class="count">{{ $index + 1 }} من {{ $count }}</span>
        <a class="{{ $next ? '' : 'disabled' }}" href="{{ $next ? route('student.topics.show', $next) : '#' }}" aria-label="التالي">@include('student.partials.icon', ['name' => 'next'])</a>
        <button type="button" data-open="tools" aria-label="أدوات">@include('student.partials.icon', ['name' => 'tools'])</button>
    </div>
</nav>

{{-- Contents --}}
<dialog class="sheet" id="toc">
    <div class="sheet-handle"></div>
    <div class="dialog-body">
        <h3>المحتويات</h3>
        <p class="muted small" style="margin:0 0 8px">{{ $booklet->title }}</p>
        <div class="list">
            @foreach ($topics as $i => $t)
                <a class="topic-item" href="{{ route('student.topics.show', $t) }}" style="{{ $t->id === $topic->id ? 'background:var(--violet-soft)' : '' }}">
                    <span class="num {{ in_array($t->id, $done) ? 'done' : '' }}">@if (in_array($t->id, $done)) @include('student.partials.icon', ['name' => 'check', 'size' => 14]) @else {{ $i + 1 }} @endif</span>
                    <span class="grow">{{ $t->title }}</span>
                </a>
            @endforeach
        </div>
    </div>
</dialog>

{{-- Edit / tools --}}
<dialog class="sheet" id="tools">
    <div class="sheet-handle"></div>
    <div class="dialog-body">
        <h3>أدوات القراءة</h3>
        <button class="tool-row" type="button" data-open="toc" data-close><span class="ic">@include('student.partials.icon', ['name' => 'list', 'size' => 18])</span><span class="grow">المحتويات</span></button>
        <div class="tool-row" style="cursor:default;flex-wrap:wrap">
            <span class="ic">@include('student.partials.icon', ['name' => 'search', 'size' => 18])</span>
            <input class="search-input grow" id="booklet-search" type="search" placeholder="ابحث داخل الملزمة…" style="min-height:40px">
            <div id="search-results" style="width:100%"></div>
        </div>
        <div class="tool-row" style="cursor:default">
            <span class="ic">@include('student.partials.icon', ['name' => 'font', 'size' => 18])</span><span class="grow">حجم النص</span>
            <span class="font-ctrl"><button type="button" data-font="-2" aria-label="تصغير">A−</button><b id="font-value">{{ $fontSize }}</b><button type="button" data-font="2" aria-label="تكبير">A+</button></span>
        </div>
        <button class="tool-row" type="button" data-fav="topic" data-id="{{ $topic->id }}" id="bookmark-row">
            <span class="ic">@include('student.partials.icon', ['name' => 'bookmark', 'size' => 18])</span><span class="grow">إشارة مرجعية لهذا الموضوع</span>
            <span class="switch {{ $isFavorite ? 'on' : '' }}" id="bookmark-switch"></span>
        </button>
        <button class="tool-row" type="button" id="hl-toggle">
            <span class="ic">@include('student.partials.icon', ['name' => 'highlight', 'size' => 18])</span>
            <span class="grow">التظليل<br><span class="muted small">فعّله ثم حدّد نصاً لتظليله، واضغط على تظليل لحذفه</span></span>
            <span class="switch" id="hl-switch"></span>
        </button>
    </div>
</dialog>

{{-- MCQ: solve or show solutions --}}
@foreach (['ministerial' => 'MCQ وزاري', 'teacher' => 'MCQ الأستاذ'] as $source => $label)
    <dialog class="sheet" id="mcq-{{ $source }}">
        <div class="sheet-handle"></div>
        <div class="dialog-body center">
            <h3>{{ $label }}</h3>
            <div class="muted">{{ $stats[$source] }} سؤال من موضوع «{{ $topic->title }}»</div>
            <div class="stack">
                <form method="POST" action="{{ route('student.attempts.store', $topic) }}">
                    @csrf <input type="hidden" name="mode" value="{{ $source }}">
                    <button class="btn btn-block" type="submit">@include('student.partials.icon', ['name' => 'pen', 'size' => 18]) ابدأ الحل</button>
                </form>
                <a class="btn btn-light btn-block" href="{{ route('student.topics.review', [$topic, $source]) }}">@include('student.partials.icon', ['name' => 'eye', 'size' => 18]) أظهر الحل</a>
            </div>
        </div>
    </dialog>
@endforeach

{{-- Quiz mode: choose difficulty --}}
<dialog class="sheet" id="quiz">
    <div class="sheet-handle"></div>
    <div class="dialog-body center">
        <h3>وضع الاختبار</h3>
        <div class="muted">اختر مستوى الصعوبة — حتى {{ $booklet->quiz_question_count }} أسئلة من هذا الموضوع</div>
        <form class="stack" method="POST" action="{{ route('student.attempts.store', $topic) }}">
            @csrf <input type="hidden" name="mode" value="quiz">
            @foreach (['easy' => 'سهل', 'medium' => 'متوسط', 'hard' => 'صعب'] as $d => $label)
                <button class="diff-btn" type="submit" name="difficulty" value="{{ $d }}" @disabled(($stats['quiz'][$d] ?? 0) === 0)>
                    <span class="pill pill-{{ $d }}">{{ $label }}</span><span class="muted small">{{ $stats['quiz'][$d] ?? 0 }} سؤال متاح</span>
                </button>
            @endforeach
        </form>
    </div>
</dialog>

<dialog id="soon">
    <div class="dialog-body center">
        <div class="empty" style="padding:4px 0 0"><span class="ic">@include('student.partials.icon', ['name' => 'sparkle', 'size' => 28])</span></div>
        <h3>قريباً</h3>
        <p class="muted">هذه الميزة ستتوفر لاحقاً.</p>
        <button class="btn btn-outline btn-block" type="button" data-close>حسناً</button>
    </div>
</dialog>

@push('scripts')
<script>
(() => {
    const top = document.getElementById('reader-top');
    const bar = document.getElementById('read-bar');
    const content = document.getElementById('content');
    const progressUrl = @json(route('student.topics.progress', $topic));
    const pct = () => {
        const h = document.documentElement.scrollHeight;
        return h <= innerHeight + 4 ? 100 : Math.min(100, Math.round((scrollY + innerHeight) / h * 100));
    };

    // Hide the top bar while reading; show it when scrolling up.
    let lastY = scrollY;
    addEventListener('scroll', () => {
        top.classList.toggle('hide', scrollY > lastY && scrollY > 80);
        lastY = scrollY;
        bar.style.width = pct() + '%';
    }, { passive: true });

    // Restore position: after an edit (highlight) or the saved reading position.
    const restore = sessionStorage.getItem('restore-y');
    if (restore !== null) { sessionStorage.removeItem('restore-y'); scrollTo(0, +restore); }
    else if (location.hash) { document.querySelector(location.hash)?.scrollIntoView({ block: 'center' }); }
    else {
        const resume = {{ (int) ($resumeAt ?? 0) }};
        if (resume > 0 && resume < 95) requestAnimationFrame(() => scrollTo(0, resume / 100 * document.documentElement.scrollHeight - innerHeight));
    }
    bar.style.width = pct() + '%';

    // Reading heartbeat (position + active reading time).
    let lastBeat = Date.now(), lastActive = Date.now();
    ['scroll', 'keydown', 'pointerdown', 'touchstart'].forEach(ev => addEventListener(ev, () => lastActive = Date.now(), { passive: true }));
    const beat = (keepalive = false) => {
        const now = Date.now();
        const active = document.visibilityState === 'visible' && now - lastActive < 120000;
        const seconds = active ? Math.min(60, Math.round((now - lastBeat) / 1000)) : 0;
        lastBeat = now;
        fetch(progressUrl, { method: 'POST', keepalive, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': Booklit.token }, body: JSON.stringify({ scroll: pct(), seconds }) }).catch(() => {});
    };
    setTimeout(beat, 3000);
    setInterval(beat, 15000);
    addEventListener('pagehide', () => beat(true));

    // Text size.
    let size = {{ $fontSize }};
    document.querySelectorAll('[data-font]').forEach(b => b.addEventListener('click', () => {
        size = Math.max(14, Math.min(26, size + +b.dataset.font));
        content.style.setProperty('--fs', size + 'px');
        document.getElementById('font-value').textContent = size;
        Booklit.post(@json(route('student.profile.font')), { font_size: size });
    }));

    // Bookmark switch mirrors the favorite state.
    document.getElementById('bookmark-row').addEventListener('click', () => setTimeout(() =>
        document.getElementById('bookmark-switch').classList.toggle('on', document.getElementById('bookmark-row').classList.contains('on')), 400));
    @if ($isFavorite) document.getElementById('bookmark-row').classList.add('on'); @endif

    // Search inside the booklet.
    let timer;
    document.getElementById('booklet-search').addEventListener('input', e => {
        clearTimeout(timer);
        const q = e.target.value.trim();
        const box = document.getElementById('search-results');
        if (q.length < 2) { box.innerHTML = ''; return; }
        timer = setTimeout(async () => {
            const r = await fetch(@json(route('student.booklets.search', $booklet)) + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
            const { results } = await r.json();
            box.replaceChildren(...(results.length ? results.map(x => {
                const a = document.createElement('a'); a.className = 'result-link'; a.href = x.url;
                const b = document.createElement('b'); b.textContent = x.topic;
                const s = document.createElement('span'); s.textContent = x.snippet;
                a.append(b, s); return a;
            }) : [Object.assign(document.createElement('p'), { className: 'muted small', textContent: 'لا توجد نتائج.' })]));
        }, 300);
    });

    // Highlighting mode: select text inside one paragraph / item / cell to save a highlight.
    const toggle = document.getElementById('hl-toggle');
    const hlOn = () => content.classList.contains('highlighting');
    toggle.addEventListener('click', () => {
        content.classList.toggle('highlighting');
        document.getElementById('hl-switch').classList.toggle('on', hlOn());
        if (hlOn()) toggle.closest('dialog').close();
    });
    const cp = s => Array.from(s).length; // offsets are Unicode code points
    const save = async () => {
        if (!hlOn()) return;
        const sel = getSelection();
        if (!sel.rangeCount || sel.isCollapsed) return;
        const range = sel.getRangeAt(0);
        const el = (range.startContainer.nodeType === 3 ? range.startContainer.parentElement : range.startContainer).closest('[data-base]');
        const endEl = (range.endContainer.nodeType === 3 ? range.endContainer.parentElement : range.endContainer).closest('[data-base]');
        if (!el || el !== endEl || !content.contains(el)) { sel.removeAllRanges(); return; }
        const pre = document.createRange(); pre.selectNodeContents(el); pre.setEnd(range.startContainer, range.startOffset);
        const start = +el.dataset.base + cp(pre.toString());
        const end = start + cp(range.toString());
        sel.removeAllRanges();
        const r = await Booklit.post(@json(route('student.highlights.store')), { block: el.closest('[data-block]').dataset.block, start, end });
        if (r.ok) { sessionStorage.setItem('restore-y', scrollY); location.reload(); }
    };
    content.addEventListener('mouseup', save);
    content.addEventListener('touchend', () => setTimeout(save, 50));
    content.addEventListener('click', async e => {
        const m = e.target.closest('mark.user');
        if (!m || !hlOn() || !confirm('حذف هذا التظليل؟')) return;
        const r = await Booklit.post(@json(url('highlights')) + '/' + m.dataset.hl, null, 'DELETE');
        if (r.ok) { sessionStorage.setItem('restore-y', scrollY); location.reload(); }
    });
})();
</script>
@endpush
@endsection
