@extends('layout')
@section('title', 'إنشاء دفتر')
@section('body-class', 'is-auth')

@section('content')
    <section class="auth">
        <div class="auth__art" aria-hidden="true">
            @include('partials.enso', ['percent' => 92])
            <span class="auth__kanji">始め</span>
        </div>
        <div class="auth__card card">
            <h1 class="title">ابدأ دفترك</h1>
            <p class="muted">أربعة أسئلة كل شهر، وصفحة صغيرة كل يوم.</p>

            @include('partials.errors')

            <form method="POST" action="{{ url('/register') }}" class="form">
                @csrf
                <label class="field">
                    <span>الاسم</span>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                </label>
                <label class="field">
                    <span>البريد الإلكتروني</span>
                    <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" dir="ltr">
                </label>
                <label class="field">
                    <span>كلمة المرور <small>(8 أحرف على الأقل)</small></span>
                    <input type="password" name="password" required autocomplete="new-password" dir="ltr">
                </label>
                <label class="field">
                    <span>تأكيد كلمة المرور</span>
                    <input type="password" name="password_confirmation" required autocomplete="new-password" dir="ltr">
                </label>
                <button class="btn btn--ink" type="submit">إنشاء الدفتر</button>
            </form>
            <p class="auth__switch">لديك دفتر؟ <a href="{{ route('login') }}">ادخل</a></p>
        </div>
    </section>
@endsection
