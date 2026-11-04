@extends('student.layout')
@section('title', 'المفضلة')
@section('tab', 'favorites')
@section('content')
@include('student.partials.appbar', ['title' => 'المفضلة'])
<main class="page">
    <div class="segmented">
        <a class="{{ $tab === 'booklets' ? 'active' : '' }}" href="{{ route('student.favorites') }}">الملازم</a>
        <a class="{{ $tab === 'topics' ? 'active' : '' }}" href="{{ route('student.favorites', ['tab' => 'topics']) }}">المواضيع</a>
    </div>

    <div class="section">
        @if ($favorites->isEmpty())
            <div class="card"><div class="empty">
                <span class="ic">@include('student.partials.icon', ['name' => 'heart', 'size' => 28])</span>
                <h3>لا توجد عناصر محفوظة</h3>
                <p>{{ $tab === 'topics' ? 'احفظ أي موضوع من «أدوات القراءة ← إشارة مرجعية» ليظهر هنا.' : 'اضغط على ♡ بجانب أي ملزمة في المكتبة لحفظها هنا.' }}</p>
                <a class="btn btn-light btn-sm" href="{{ route('student.library') }}">تصفح المكتبة</a>
            </div></div>
        @else
            <div class="card list">
                @foreach ($favorites as $fav)
                    @php($item = $fav->favoritable)
                    <div class="item" data-item>
                        <span class="ic">@include('student.partials.icon', ['name' => $tab === 'topics' ? 'bookmark' : 'book', 'size' => 18])</span>
                        <a class="grow" href="{{ $tab === 'topics' ? route('student.topics.show', $item) : route('student.booklets.show', $item) }}">
                            <b class="ellipsis" style="display:block">{{ $item->title }}</b>
                            <span class="muted small">
                                @if ($tab === 'topics') {{ $item->booklet->title }} · {{ $item->booklet->subject->name }}
                                @else {{ $item->subject->name }} · {{ $item->subject->stage->name }} @endif
                            </span>
                        </a>
                        <button class="fav-btn on" type="button" data-fav="{{ $tab === 'topics' ? 'topic' : 'booklet' }}" data-id="{{ $item->id }}" data-remove-on-off="1" aria-label="إزالة من المفضلة">@include('student.partials.icon', ['name' => 'heart', 'size' => 18])</button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</main>
@endsection
