@extends('student.layout')
@section('title', 'تسجيل الدخول')
@section('content')
<main class="auth">
    <div class="auth-box">
        <div class="auth-logo">
            @include('student.partials.icon', ['name' => 'book', 'size' => 64])
            <h1>بوكلت</h1>
            <div class="muted">ملازم وأسئلة طب الأسنان</div>
        </div>
        <div class="card"><div class="card-body">
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
                <button class="btn btn-block" type="submit">تسجيل الدخول</button>
            </form>
            <p class="center" style="margin:14px 0 0">ليس لديك حساب؟ <a href="{{ route('student.register') }}">أنشئ حساباً جديداً</a></p>
        </div></div>
        <p class="center muted small">ملاحظة: يرتبط الحساب بجهاز واحد فقط.</p>
    </div>
</main>
@endsection
