@extends('student.layout')
@section('title', 'تسجيل الدخول')
@section('content')
<main class="container">
    <section class="auth">
        <div class="intro">
            <h1>مرحباً بعودتك<br>إلى <span style="color:var(--yellow)">بوكلت</span></h1>
            <p class="muted" style="max-width:460px">سجّل الدخول لتكمل قراءة ملازمك وحل أسئلة الوزاري والأستاذ.</p>
            <div class="cards3" style="max-width:560px;margin-top:20px">
                <div class="folder f-blue" style="min-height:120px"><span class="kicker">ملازم</span><span class="title">لكل المراحل</span></div>
                <div class="folder f-gray" style="min-height:120px"><span class="kicker">أسئلة</span><span class="title">مع الشرح</span></div>
                <div class="folder f-yellow" style="min-height:120px"><span class="kicker">نتائج</span><span class="title">فورية</span></div>
            </div>
        </div>
        <div class="folder f-dark panel">
            <h2>تسجيل الدخول</h2>
            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('student.login.submit') }}">
                @csrf
                <div class="field">
                    <label for="phone">رقم الهاتف</label>
                    <input class="input ltr" id="phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="07XXXXXXXXX" value="{{ old('phone') }}" required autofocus>
                </div>
                <div class="field">
                    <label for="password">كلمة المرور</label>
                    <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn-block" type="submit">دخول</button>
            </form>
            <p class="center muted" style="margin:18px 0 0">ليس لديك حساب؟ <a href="{{ route('student.register') }}" style="color:#fff;font-weight:700">أنشئ حساباً</a></p>
            <p class="center muted small" style="margin:6px 0 0">يرتبط الحساب بجهاز واحد فقط.</p>
        </div>
    </section>
</main>
@endsection
