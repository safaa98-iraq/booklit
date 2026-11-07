@extends('layout')
@section('title', 'تسجيل الدخول')
@section('body-class', 'is-auth')

@section('content')
    <section class="auth">
        <div class="auth__art" aria-hidden="true">
            @include('partials.enso', ['percent' => 92])
            <span class="auth__kanji">家計簿</span>
        </div>
        <div class="auth__card card">
            <h1 class="title">دفتر كاكيبو</h1>
            <p class="muted">دوِّن ما تنفق بهدوء، وتأمّل في نهاية كل شهر.</p>

            @include('partials.errors')

            <form method="POST" action="{{ url('/login') }}" class="form">
                @csrf
                <label class="field">
                    <span>البريد الإلكتروني</span>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" dir="ltr">
                </label>
                <label class="field">
                    <span>كلمة المرور</span>
                    <input type="password" name="password" required autocomplete="current-password" dir="ltr">
                </label>
                <label class="check">
                    <input type="checkbox" name="remember" value="1"> تذكّرني
                </label>
                <button class="btn btn--ink" type="submit">دخول</button>
            </form>
            <p class="auth__switch">ليس لديك دفتر؟ <a href="{{ route('register') }}">أنشئ دفترك</a></p>
        </div>
    </section>
@endsection
