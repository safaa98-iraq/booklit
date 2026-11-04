@extends('student.layout')
@section('title', 'الإشعارات')
@section('tab', 'home')
@section('content')
@include('student.partials.appbar', ['title' => 'الإشعارات', 'back' => route('student.home')])
<main class="page">
    @if (empty($items))
        <div class="card"><div class="empty"><span class="ic">@include('student.partials.icon', ['name' => 'bell', 'size' => 28])</span><h3>لا توجد إشعارات</h3><p>ستظهر هنا تنبيهات الاشتراك والملازم الجديدة.</p></div></div>
    @else
        <div class="card list">
            @foreach ($items as $n)
                @php($inner = '<span class="ic">'.view('student.partials.icon', ['name' => $n['icon'], 'size' => 18])->render().'</span><span class="grow"><b>'.e($n['title']).'</b><br><span class="muted small">'.e($n['body']).'</span></span><span class="muted small">'.e($n['date']->diffForHumans()).'</span>')
                @if (isset($n['url']))
                    <a class="item" href="{{ $n['url'] }}">{!! $inner !!}</a>
                @else
                    <div class="item">{!! $inner !!}</div>
                @endif
            @endforeach
        </div>
    @endif
</main>
@endsection
