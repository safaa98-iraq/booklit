<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3eee4" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1c1a17" media="(prefers-color-scheme: dark)">
    <title>@yield('title', 'دفتر كاكيبو') · 家計簿</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect x='6' y='6' width='52' height='52' rx='10' fill='%23b5452f'/%3E%3Ctext x='32' y='44' font-size='34' text-anchor='middle' fill='%23fbf8f2' font-family='serif'%3E家%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Noto+Serif+JP:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kakeibo.css') }}?v=3">
</head>
<body class="@yield('body-class')">
    {{-- Brush texture shared by every ensō on the page --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <filter id="brush">
            <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="3" result="noise"/>
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="3.5"/>
        </filter>
    </svg>

    @auth
        <header class="topbar">
            <div class="topbar__inner">
                <a href="{{ route('home') }}" class="brand" aria-label="دفتر كاكيبو — الصفحة الرئيسية">
                    <span class="hanko" aria-hidden="true">家計簿</span>
                    <span class="brand__text">
                        <strong>دفتر كاكيبو</strong>
                        <small>فنّ الادّخار الياباني</small>
                    </span>
                </a>
                <nav class="nav" aria-label="التنقل الرئيسي">
                    <a href="{{ route('home') }}" @class(['is-active' => request()->routeIs('months.show', 'entries.*')])>الشهر</a>
                    <a href="{{ route('months.index') }}" @class(['is-active' => request()->routeIs('months.index')])>السنة</a>
                    <a href="{{ route('settings') }}" @class(['is-active' => request()->routeIs('settings')])>الإعدادات</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nav__out">خروج</button>
                    </form>
                </nav>
            </div>
        </header>
    @endauth

    <main class="page">
        @if (session('status'))
            <div class="toast" role="status"><span class="toast__dot" aria-hidden="true"></span>{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    <footer class="footer">
        <div class="seigaiha" aria-hidden="true"></div>
        <p class="footer__proverb">
            <span lang="ja">塵も積もれば山となる</span>
            <span>حتى الغبار، إذا تراكم، صار جبلاً.</span>
        </p>
    </footer>

    <script>
        // Ask before deleting anything.
        document.addEventListener('submit', function (e) {
            var msg = e.target.getAttribute('data-confirm');
            if (msg && !window.confirm(msg)) e.preventDefault();
        });
    </script>
</body>
</html>
