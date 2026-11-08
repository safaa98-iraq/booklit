{{-- Ensō (円相) ring. $percent: 0–100 share of the budget already used. --}}
@php
    $r = 54;
    $c = 2 * M_PI * $r;
    $p = max(0, min(100, $percent ?? 0));
    $tone = $p >= 100 ? 'var(--beni)' : ($p >= 80 ? 'var(--kincha)' : 'url(#pastel-ink)');
@endphp
<svg class="enso" viewBox="0 0 140 140" role="img" aria-label="استُخدم {{ $p }}٪ من المتاح">
    <circle cx="70" cy="70" r="{{ $r }}" class="enso__track"/>
    <circle cx="70" cy="70" r="{{ $r }}" class="enso__ink" filter="url(#brush)"
            stroke="{{ $tone }}"
            stroke-dasharray="{{ round($c * $p / 100, 2) }} {{ round($c, 2) }}"
            transform="rotate(-100 70 70)"/>
</svg>
