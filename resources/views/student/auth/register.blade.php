@extends('student.layout')
@section('title', 'إنشاء حساب')
@section('content')
<main class="auth">
    <div class="auth-box">
        <div class="auth-logo">
            @include('student.partials.icon', ['name' => 'book', 'size' => 56])
            <h1>إنشاء حساب</h1>
            <div class="muted">سجّل لتصفح الملازم وحل الأسئلة</div>
        </div>
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ route('student.register.submit') }}">
                @csrf
                <div class="field">
                    <label for="name">الاسم الكامل</label>
                    <input class="input" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
                    @error('name')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="phone">رقم الهاتف</label>
                    <input class="input ltr" id="phone" name="phone" type="tel" inputmode="tel" placeholder="07XXXXXXXXX" value="{{ old('phone') }}" required>
                    @error('phone')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password">كلمة المرور</label>
                    <input class="input" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    @error('password')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">تأكيد كلمة المرور</label>
                    <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </div>
                <button class="btn btn-block" type="submit">إنشاء الحساب</button>
            </form>
            <p class="center" style="margin:14px 0 0">لديك حساب؟ <a href="{{ route('student.login') }}">سجّل الدخول</a></p>
        </div></div>
    </div>
</main>
@endsection
