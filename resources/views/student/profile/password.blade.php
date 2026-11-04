@extends('student.layout')
@section('title', 'تغيير كلمة المرور')
@section('tab', 'profile')
@section('content')
@include('student.partials.appbar', ['title' => 'تغيير كلمة المرور', 'back' => route('student.profile')])
<main class="page">
    <form class="card card-body" method="POST" action="{{ route('student.profile.password.update') }}">
        @csrf @method('PUT')
        <div class="field">
            <label for="current_password">كلمة المرور الحالية</label>
            <input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password" required>
            @error('current_password')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password">كلمة المرور الجديدة</label>
            <input class="input" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
            @error('password')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
            <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        </div>
        <button class="btn btn-block" type="submit">تغيير كلمة المرور</button>
    </form>
</main>
@endsection
