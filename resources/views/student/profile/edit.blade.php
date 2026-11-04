@extends('student.layout')
@section('title', 'تعديل الحساب')
@section('tab', 'profile')
@section('content')
@include('student.partials.appbar', ['title' => 'تعديل الحساب', 'back' => route('student.profile')])
<main class="page">
    <form class="card card-body" method="POST" action="{{ route('student.profile.update') }}">
        @csrf @method('PUT')
        <div class="field">
            <label for="name">الاسم الكامل</label>
            <input class="input" id="name" name="name" value="{{ old('name', $student->name) }}" required>
            @error('name')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="email">البريد الإلكتروني (اختياري)</label>
            <input class="input ltr" id="email" name="email" type="email" value="{{ old('email', $student->email) }}">
            @error('email')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label>رقم الهاتف</label>
            <input class="input ltr" value="{{ $student->phone }}" disabled>
            <div class="muted small" style="margin-top:4px">لتغيير رقم الهاتف تواصل مع الإدارة.</div>
        </div>
        <button class="btn btn-block" type="submit">حفظ</button>
    </form>
</main>
@endsection
