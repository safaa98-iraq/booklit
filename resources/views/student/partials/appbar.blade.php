{{-- Simple page header: back button + title. $back (url|null), $title --}}
<header class="appbar {{ ! empty($wide) ? 'wide' : '' }}">
    <div class="inner">
        @if (! empty($back))
            <a class="icon-btn ghost" href="{{ $back }}" aria-label="رجوع">@include('student.partials.icon', ['name' => 'back'])</a>
        @endif
        <h1 class="grow ellipsis">{{ $title }}</h1>
        {{ $slot ?? '' }}
    </div>
</header>
