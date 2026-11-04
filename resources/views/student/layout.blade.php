<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#F5F4FF">
    <title>@yield('title', 'BOOKLIT') — BOOKLIT</title>
    <link rel="preload" href="/fonts/Cairo.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/css/student.css?v={{ filemtime(public_path('css/student.css')) }}">
    @stack('head')
</head>
<body @hasSection('protected') oncontextmenu="return false" oncopy="return false" @endif>
    @yield('content')

    @hasSection('tab')
        @include('student.partials.tabbar', ['active' => trim($__env->yieldContent('tab'))])
    @endif

    @auth('student')
        @php($wmUser = auth('student')->user())
        {{-- Dynamic watermark: the student's name and phone. --}}
        @php($wm = e($wmUser->name.' • '.$wmUser->phone))
        <div class="watermark" style="background-image: url(&quot;data:image/svg+xml;utf8,{{ rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="170"><text x="150" y="95" fill="rgba(23,23,43,0.05)" font-size="14" font-family="Cairo, sans-serif" text-anchor="middle" transform="rotate(-24 150 85)">'.$wm.'</text></svg>') }}&quot;)"></div>
    @endauth

    <script>
        window.Booklit = {
            token: document.querySelector('meta[name=csrf-token]').content,
            post(url, data, method = 'POST') {
                return fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.token }, body: data ? JSON.stringify(data) : null });
            },
        };
        document.addEventListener('click', e => { if (e.target.tagName === 'DIALOG') e.target.close(); });
        document.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => document.getElementById(b.dataset.open)?.showModal()));
        document.addEventListener('click', e => { const c = e.target.closest('[data-close]'); if (c) c.closest('dialog')?.close(); });
        // Favorite buttons: <button data-fav="booklet|topic" data-id="…" class="fav-btn">
        document.addEventListener('click', async e => {
            const b = e.target.closest('[data-fav]');
            if (!b) return;
            e.preventDefault(); e.stopPropagation();
            const r = await Booklit.post(@json(route('student.favorites.toggle')), { type: b.dataset.fav, id: +b.dataset.id });
            if (r.ok) { const { favorite } = await r.json(); b.classList.toggle('on', favorite); b.setAttribute('aria-pressed', favorite); if (b.dataset.removeOnOff && !favorite) b.closest('[data-item]')?.remove(); }
        });
    </script>
    @stack('scripts')
</body>
</html>
