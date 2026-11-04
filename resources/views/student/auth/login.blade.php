@extends('student.layout')
@section('title', 'تسجيل الدخول')
@section('content')
<main class="auth">
    <span class="logo">BOOKLIT</span>
    <h1>مرحباً بعودتك</h1>
    <p class="center muted" style="margin:0 0 22px">ملازم وأسئلة طب الأسنان في مكان واحد</p>
    <div class="card"><div class="card-body">
        @if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
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
            <button class="btn btn-block" type="submit">تسجيل الدخول</button>
        </form>
    </div></div>
    <p class="center" style="margin:16px 0 4px">ليس لديك حساب؟ <a class="link" href="{{ route('student.register') }}">أنشئ حساباً</a></p>
    <p class="center muted small">يرتبط الحساب بجهاز واحد فقط.</p>
</main>
@endsection
