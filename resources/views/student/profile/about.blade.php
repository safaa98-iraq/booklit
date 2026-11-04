@extends('student.layout')
@section('title', 'عن BOOKLIT')
@section('tab', 'profile')
@section('content')
@include('student.partials.appbar', ['title' => 'عن BOOKLIT', 'back' => route('student.profile')])
<main class="page">
    <div class="card card-body center">
        <span class="logo" style="font-size:1.6rem">BOOKLIT</span>
        <p class="muted">منصة ملازم وأسئلة لطلبة طب الأسنان: ملازم منظمة حسب المرحلة والمادة، أسئلة وزارية وأسئلة الأستاذ مع الشرح، واختبارات تربط كل إجابة بمصدرها في الملزمة.</p>
        <p class="muted small">الإصدار 1.0</p>
    </div>
</main>
@endsection
