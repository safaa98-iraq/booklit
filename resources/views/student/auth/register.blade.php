@extends('student.layout')
@section('title', 'إنشاء حساب')
@section('content')
<main class="container">
    <section class="auth">
        <div class="intro">
            <h1>ابدأ رحلتك<br>في <span style="color:var(--lavender)">طب الأسنان</span></h1>
            <p class="muted" style="max-width:460px">أنشئ حسابك مجاناً بالاسم ورقم الهاتف. المواد غير الوزارية متاحة فوراً.</p>
            <div class="cards3" style="max-width:560px;margin-top:20px">
                <div class="folder f-lavender" style="min-height:120px"><span class="kicker">الخطوة 1</span><span class="title">أنشئ حسابك</span></div>
                <div class="folder f-gray" style="min-height:120px"><span class="kicker">الخطوة 2</span><span class="title">اختر مادتك</span></div>
                <div class="folder f-yellow" style="min-height:120px"><span class="kicker">الخطوة 3</span><span class="title">ادرس وحل</span></div>
            </div>
        </div>
        <div class="folder f-dark panel">
            <h2>إنشاء حساب</h2>
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
            <p class="center muted" style="margin:18px 0 0">لديك حساب؟ <a href="{{ route('student.login') }}" style="color:#fff;font-weight:700">سجّل الدخول</a></p>
        </div>
    </section>
</main>
@endsection
