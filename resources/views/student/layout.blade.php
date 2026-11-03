<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a0a0a">
    <title>@yield('title', 'بوكلت') — بوكلت</title>
    <link rel="preload" href="/fonts/Cairo.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/css/student.css?v={{ filemtime(public_path('css/student.css')) }}">
    @stack('head')
</head>
<body @hasSection('protected') oncontextmenu="return false" oncopy="return false" @endif>
    <header class="nav">
        <div class="container">
            <div class="nav-start">
                @hasSection('back')
                    <a class="icon-btn" href="@yield('back')" aria-label="رجوع">@include('student.partials.icon', ['name' => 'arrow', 'size' => 18])</a>
                @endif
                @auth('student')
                    <a class="nav-link {{ request()->routeIs('student.home') ? 'active' : '' }}" href="{{ route('student.home') }}">الرئيسية</a>
                    <a class="nav-link {{ request()->routeIs('student.account') ? 'active' : '' }}" href="{{ route('student.account') }}">محاولاتي</a>
                @else
                    <a class="nav-link" href="{{ route('student.home') }}#features">المنصة</a>
                    <a class="nav-link" href="{{ route('student.home') }}#stages">المراحل</a>
                    @if ($telegram = app(\App\Services\SettingsService::class)->get('telegram_url'))
                        <a class="nav-link" href="{{ $telegram }}" target="_blank" rel="noopener">تواصل</a>
                    @endif
                @endauth
            </div>
            <a class="brand" href="{{ route('student.home') }}">@hasSection('nav-title')@yield('nav-title')@else بوكلت @endif</a>
            <div class="nav-end">
                @yield('topbar-actions')
                @auth('student')
                    <a class="avatar" href="{{ route('student.account') }}" title="حسابي">{{ mb_substr(auth('student')->user()->name, 0, 1) }}</a>
                @else
                    <a class="nav-link" href="{{ route('student.login') }}">تسجيل الدخول</a>
                    <a class="btn btn-sm" href="{{ route('student.register') }}">إنشاء حساب</a>
                @endauth
            </div>
        </div>
    </header>

    @yield('content')

    @auth('student')
        @php($student = auth('student')->user())
        {{-- Dynamic watermark: the student's name and phone over every page. --}}
        @php($wm = e($student->name.' • '.$student->phone))
        <div class="watermark" style="background-image: url(&quot;data:image/svg+xml;utf8,{{ rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="170"><text x="150" y="95" fill="rgba(255,255,255,0.06)" font-size="15" font-family="Cairo, sans-serif" text-anchor="middle" transform="rotate(-24 150 85)">'.$wm.'</text></svg>') }}&quot;)"></div>
    @endauth

    <script>
        // Close any <dialog> when its backdrop is clicked.
        document.addEventListener('click', e => { if (e.target.tagName === 'DIALOG') e.target.close(); });
        document.querySelectorAll('[data-open]').forEach(b => b.addEventListener('click', () => document.getElementById(b.dataset.open)?.showModal()));
        document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => b.closest('dialog')?.close()));
    </script>
    @stack('scripts')
</body>
</html>
