@extends('layout')
@section('title', 'الإعدادات')

@section('content')
    <section class="card narrow">
        <h1 class="section-title"><span lang="ja">設定</span> الإعدادات</h1>
        @if ($errors->hasAny(['name', 'currency']))
            @include('partials.errors')
        @endif
        <form method="POST" action="{{ route('settings.update') }}" class="form">
            @csrf @method('PUT')
            <label class="field">
                <span>الاسم</span>
                <input type="text" name="name" required maxlength="80" value="{{ old('name', auth()->user()->name) }}">
            </label>
            <label class="field">
                <span>رمز العملة <small>(مثل: د.ع، $، ر.س، ¥)</small></span>
                <input type="text" name="currency" required maxlength="10" value="{{ old('currency', auth()->user()->currency) }}">
            </label>
            <button class="btn btn--ink" type="submit">حفظ</button>
        </form>
    </section>
@endsection
