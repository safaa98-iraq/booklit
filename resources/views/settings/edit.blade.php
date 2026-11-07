@extends('layout')
@section('title', 'الإعدادات')

@section('content')
    <section class="card narrow">
        <h1 class="section-title"><span lang="ja">設定</span> الإعدادات</h1>
        @if ($errors->hasAny(['name', 'email', 'currency']))
            @include('partials.errors')
        @endif
        <form method="POST" action="{{ route('settings.update') }}" class="form">
            @csrf @method('PUT')
            <label class="field">
                <span>الاسم</span>
                <input type="text" name="name" required maxlength="80" value="{{ old('name', auth()->user()->name) }}">
            </label>
            <label class="field">
                <span>البريد الإلكتروني</span>
                <input type="email" name="email" required maxlength="160" dir="ltr" value="{{ old('email', auth()->user()->email) }}">
            </label>
            <label class="field">
                <span>رمز العملة <small>(مثل: د.ع، $، ر.س، ¥)</small></span>
                <input type="text" name="currency" required maxlength="10" value="{{ old('currency', auth()->user()->currency) }}">
            </label>
            <button class="btn btn--ink" type="submit">حفظ</button>
        </form>
    </section>

    <section class="card narrow danger">
        <h2 class="section-title">حذف الدفتر</h2>
        <p class="muted">يُحذف حسابك وكل الأشهر والمصاريف والتعليقات نهائياً.</p>
        @if ($errors->has('password'))
            @include('partials.errors')
        @endif
        <form method="POST" action="{{ route('settings.destroy') }}" class="form" data-confirm="سيُحذف الدفتر كله نهائياً. متأكد؟">
            @csrf @method('DELETE')
            <label class="field">
                <span>كلمة المرور للتأكيد</span>
                <input type="password" name="password" required autocomplete="current-password" dir="ltr">
            </label>
            <button class="btn btn--danger" type="submit">حذف الدفتر نهائياً</button>
        </form>
    </section>
@endsection
