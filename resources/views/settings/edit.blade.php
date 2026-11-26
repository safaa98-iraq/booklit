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

    <section class="card narrow" id="backup">
        <h2 class="section-title"><span lang="ja">保存</span> حفظ بياناتك</h2>
        <p class="muted backup__lead">بياناتك محفوظة في قاعدة البيانات على الخادم. نزّل نسخة احتياطية بين حين وآخر واحتفظ بها في هاتفك أو في Google Drive، لتستعيد دفترك كاملاً متى احتجت.</p>

        <div class="backup__actions">
            <a class="btn btn--ink" href="{{ route('backup.download') }}" download>⬇ تنزيل نسخة احتياطية</a>
            <a class="btn btn--ghost" href="{{ route('backup.csv') }}" download>جدول Excel للمصاريف</a>
        </div>

        @if ($errors->has('backup'))
            @include('partials.errors')
        @endif
        <form method="POST" action="{{ route('backup.restore') }}" enctype="multipart/form-data" class="form backup__restore"
              data-confirm="الاستعادة تستبدل كل ما في الدفتر الآن بمحتوى الملف. متابعة؟">
            @csrf
            <label class="field">
                <span>استعادة من نسخة احتياطية <small>(ملف ‎.json‎)</small></span>
                <input type="file" name="backup" accept=".json,application/json" required>
            </label>
            <button class="btn btn--ghost" type="submit">استعادة</button>
        </form>
    </section>
@endsection
