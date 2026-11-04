@extends('student.layout')
@section('title', 'إنشاء حساب')
@section('content')
<main class="auth">
    <span class="logo">BOOKLIT</span>
    <h1>إنشاء حساب</h1>
    <p class="center muted" style="margin:0 0 22px">سجّل بالاسم ورقم الهاتف، والمواد المجانية متاحة فوراً</p>
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
    </div></div>
    <p class="center" style="margin:16px 0">لديك حساب؟ <a class="link" href="{{ route('student.login') }}">سجّل الدخول</a></p>
</main>
@endsection
