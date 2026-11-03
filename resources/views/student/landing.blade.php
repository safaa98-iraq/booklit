@extends('student.layout')
@section('title', 'ملازم وأسئلة طب الأسنان')
@section('content')
<main class="container">
    <section class="hero">
        <div>
            <h1>
                ادرس طب الأسنان<br>
                بطريقة <span class="faces"><span style="background:var(--lavender)">🦷</span><span style="background:var(--yellow)">📘</span><span style="background:var(--mint)">✅</span></span>أذكى
            </h1>
            <p class="lead">ملازم منظمة لكل المراحل، أسئلة وزارية وأسئلة الأستاذ مع الشرح، واختبارات تُظهر لك مصدر كل إجابة داخل الملزمة.</p>
            <div class="hero-actions">
                <a class="cta" href="{{ route('student.register') }}">ابدأ الدراسة مجاناً <span class="arrow">@include('student.partials.icon', ['name' => 'arrow', 'size' => 20])</span></a>
                <a class="play" href="#features"><i>▶</i><span>تعرّف أكثر<br>على المنصة</span></a>
            </div>
            <div class="pills" id="stages">
                @foreach ($stages as $s)
                    <a class="chip" href="{{ route('student.login') }}">{{ $s->name }}</a>
                @endforeach
            </div>
        </div>
        <div class="folder f-lavender hero-art">@include('student.partials.hero-art')</div>
    </section>

    <section class="cards3" id="features">
        <div class="folder f-blue">
            <span class="kicker">ملازم كلية طب الأسنان</span>
            <span class="title" style="font-size:1.5rem">{{ $stats['booklets'] }} ملزمة<br>لـ {{ $stats['stages'] }} مراحل دراسية</span>
        </div>
        <div class="folder f-gray">
            <span class="kicker">وزاري · الأستاذ · وضع الاختبار</span>
            <span class="corner">@include('student.partials.icon', ['name' => 'arrow-up', 'size' => 26])</span>
            <span style="font-size:3rem;line-height:1">✱</span>
        </div>
        <div class="folder f-yellow">
            <span class="kicker">سؤال محلول مع الشرح</span>
            <span class="corner">@include('student.partials.icon', ['name' => 'arrow-up', 'size' => 26])</span>
            <span class="big">{{ $stats['questions'] }}</span>
        </div>
    </section>
</main>
<footer class="footer"><div class="container">© {{ date('Y') }} بوكلت — جميع الحقوق محفوظة</div></footer>
@endsection
