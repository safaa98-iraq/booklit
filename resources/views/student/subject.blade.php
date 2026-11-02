@extends('student.layout')
@section('title', $subject->name)
@section('back', route('student.home'))
@section('content')
<main class="container narrow" style="padding-top:16px">
    @if ($booklets->isEmpty())
        <div class="card"><div class="card-body center muted">لا توجد ملازم منشورة لهذه المادة بعد.</div></div>
    @else
        <div class="card">
            @foreach ($booklets as $booklet)
                <a class="list-item" href="{{ route('student.booklets.show', $booklet) }}">
                    <span class="avatar">@include('student.partials.icon', ['name' => 'book', 'size' => 18])</span>
                    <span class="grow"><strong>{{ $booklet->title }}</strong><br><span class="muted small">{{ $booklet->description }}</span></span>
                </a>
            @endforeach
        </div>
    @endif
</main>
@endsection
