<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0d9488">
    <title>@yield('title', 'بوكلت') — بوكلت</title>
    <link rel="preload" href="/fonts/Cairo.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="/css/student.css?v={{ filemtime(public_path('css/student.css')) }}">
    @stack('head')
</head>
<body @hasSection('protected') oncontextmenu="return false" oncopy="return false" @endif>
    @auth('student')
        @php($student = auth('student')->user())
        <header class="topbar">
            <div class="container">
                @hasSection('back')
                    <a class="icon-btn" href="@yield('back')" aria-label="رجوع">@include('student.partials.icon', ['name' => 'arrow'])</a>
                    <div class="title">@yield('title')</div>
                @else
                    <a class="brand" href="{{ route('student.home') }}">@include('student.partials.icon', ['name' => 'book', 'size' => 26]) بوكلت</a>
                @endif
                <div class="spacer"></div>
                @yield('topbar-actions')
                <a class="avatar" href="{{ route('student.account') }}" title="حسابي">{{ mb_substr($student->name, 0, 1) }}</a>
            </div>
        </header>
    @endauth

    @yield('content')

    @auth('student')
        {{-- Dynamic watermark: the student's name and phone over every page. --}}
        @php($wm = e($student->name.' • '.$student->phone))
        <div class="watermark" style="background-image: url(&quot;data:image/svg+xml;utf8,{{ rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="300" height="170"><text x="150" y="95" fill="rgba(0,0,0,0.07)" font-size="15" font-family="Cairo, sans-serif" text-anchor="middle" transform="rotate(-24 150 85)">'.$wm.'</text></svg>') }}&quot;)"></div>
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
