<nav class="tabbar" aria-label="التنقل">
    <div class="inner">
        @foreach ([
            'home' => ['الرئيسية', 'home', route('student.home')],
            'library' => ['المكتبة', 'library', route('student.library')],
            'progress' => ['التقدم', 'chart', route('student.progress')],
            'favorites' => ['المفضلة', 'heart', route('student.favorites')],
            'profile' => ['حسابي', 'user', route('student.profile')],
        ] as $key => [$label, $icon, $url])
            <a class="tab {{ $active === $key ? 'active' : '' }}" href="{{ $url }}" @if ($active === $key) aria-current="page" @endif>
                @include('student.partials.icon', ['name' => $icon, 'size' => 22]){{ $label }}
            </a>
        @endforeach
    </div>
</nav>
