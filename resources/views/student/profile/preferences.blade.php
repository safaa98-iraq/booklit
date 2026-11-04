@extends('student.layout')
@php($titles = ['notifications' => 'الإشعارات', 'appearance' => 'المظهر', 'language' => 'اللغة'])
@section('title', $titles[$page])
@section('tab', 'profile')
@section('content')
@include('student.partials.appbar', ['title' => $titles[$page], 'back' => route('student.profile')])
<main class="page">
    <form class="card card-body" method="POST" action="{{ route('student.profile.preferences.save', $page) }}">
        @csrf @method('PUT')
        @if ($page === 'notifications')
            <label class="row between" style="cursor:pointer">
                <span><b>إشعارات المنصة</b><br><span class="muted small">تنبيهات انتهاء الاشتراك والملازم الجديدة في الصفحة الرئيسية</span></span>
                <input type="checkbox" name="notifications" value="1" @checked($student->preference('notifications')) style="width:22px;height:22px;accent-color:var(--violet)">
            </label>
        @elseif ($page === 'appearance')
            <div class="field">
                <label for="font_size">حجم نص القراءة: <b id="fs-out">{{ $student->preference('font_size') }}</b></label>
                <input id="font_size" name="font_size" type="range" min="14" max="26" step="1" value="{{ $student->preference('font_size') }}" style="width:100%;accent-color:var(--violet)" oninput="document.getElementById('fs-out').textContent=this.value;document.getElementById('fs-demo').style.fontSize=this.value+'px'">
            </div>
            <p id="fs-demo" class="card card-body" style="font-size: {{ $student->preference('font_size') }}px;line-height:2;margin:0 0 14px">الخلية هي الوحدة الأساسية للتركيب والوظيفة في الكائنات الحية.</p>
            <p class="muted small">المظهر الحالي: فاتح (أبيض و Lavender).</p>
        @else
            <label class="row between" style="cursor:pointer">
                <span><b>العربية</b><br><span class="muted small">لغة واجهة التطبيق</span></span>
                <input type="radio" name="language" value="ar" checked style="width:22px;height:22px;accent-color:var(--violet)">
            </label>
            <p class="muted small">ستتوفر لغات أخرى لاحقاً.</p>
        @endif
        <button class="btn btn-block" type="submit" style="margin-top:16px">حفظ</button>
    </form>
</main>
@endsection
