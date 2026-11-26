<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#fffaf6">
    <meta name="color-scheme" content="light">
    <meta name="format-detection" content="telephone=no">
    <title>@yield('title', 'دفتر كاكيبو') · 家計簿</title>

    {{-- Installable on the phone's home screen --}}
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="كاكيبو">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Noto+Serif+JP:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kakeibo.css') }}?v=8">
</head>
<body class="@yield('body-class')">
    {{-- Brush texture shared by every ensō on the page --}}
    <svg width="0" height="0" style="position:absolute" aria-hidden="true">
        <filter id="brush">
            <feTurbulence type="fractalNoise" baseFrequency="0.9" numOctaves="2" seed="3" result="noise"/>
            <feDisplacementMap in="SourceGraphic" in2="noise" scale="3.5"/>
        </filter>
        <linearGradient id="pastel-ink" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#f59ab2"/>
            <stop offset=".5" stop-color="#b9a3f0"/>
            <stop offset="1" stop-color="#8cc7e8"/>
        </linearGradient>
    </svg>

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
            </nav>
        </div>
    </header>

    <main class="page">
        @if (session('status'))
            <div class="toast" role="status"><span class="toast__dot" aria-hidden="true"></span>{{ session('status') }}</div>
        @endif

        <div class="backup-nudge" data-backup-nudge hidden>
            <span>مرّ أسبوع على آخر نسخة احتياطية لدفترك.</span>
            <a href="{{ route('backup.download') }}" download data-backup-link>نزّلها الآن</a>
            <button type="button" class="icon-btn" data-backup-dismiss aria-label="لاحقاً">×</button>
        </div>

        @yield('content')
    </main>

    @include('partials.calculator')

    <footer class="footer">
        <div class="seigaiha" aria-hidden="true"></div>
        <p class="footer__proverb">
            <span lang="ja">塵も積もれば山となる</span>
            <span>حتى الغبار، إذا تراكم، صار جبلاً.</span>
        </p>
    </footer>

    {{-- Phone: app-style tab bar --}}
    <nav class="tabbar" aria-label="التنقل">
        <a href="{{ route('home') }}" @class(['tabbar__item', 'is-active' => request()->routeIs('months.show', 'entries.*')])>
            <span class="tabbar__icon" lang="ja" aria-hidden="true">月</span><span>الشهر</span>
        </a>
        <a href="{{ route('months.index') }}" @class(['tabbar__item', 'is-active' => request()->routeIs('months.index')])>
            <span class="tabbar__icon" lang="ja" aria-hidden="true">年</span><span>السنة</span>
        </a>
        <a href="{{ request()->routeIs('months.show') ? '#add' : route('home').'#add' }}" class="tabbar__add" data-go-add aria-label="تدوين مصروف">
            <span aria-hidden="true">＋</span>
        </a>
        <button type="button" class="tabbar__item" data-calc-open>
            <span class="tabbar__icon" aria-hidden="true">🧮</span><span>الحاسبة</span>
        </button>
        <a href="{{ route('settings') }}" @class(['tabbar__item', 'is-active' => request()->routeIs('settings')])>
            <span class="tabbar__icon" lang="ja" aria-hidden="true">設</span><span>الإعدادات</span>
        </a>
    </nav>

    <script src="{{ asset('js/kakeibo.js') }}?v=3" defer></script>
    <script>
        // Ask before deleting anything.
        document.addEventListener('submit', function (e) {
            var msg = e.target.getAttribute('data-confirm');
            if (msg && !window.confirm(msg)) e.preventDefault();
        });
        // Works like an app on the phone (home-screen install, offline page).
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
        }
    </script>
</body>
</html>
