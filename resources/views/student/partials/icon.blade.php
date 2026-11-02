@php
$paths = [
    'book' => '<path d="M4 5a2 2 0 0 1 2-2h5v16H6a2 2 0 0 0-2 2V5Z"/><path d="M20 5a2 2 0 0 0-2-2h-5v16h5a2 2 0 0 1 2 2V5Z"/>',
    'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    'check' => '<path d="m5 12 5 5L20 7"/>',
    'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
    'badge' => '<circle cx="12" cy="9" r="6"/><path d="m9 14-2 7 5-3 5 3-2-7"/>',
    'bank' => '<path d="M3 10 12 4l9 6"/><path d="M5 10v8M9 10v8M15 10v8M19 10v8M3 20h18"/>',
    'school' => '<path d="m2 9 10-5 10 5-10 5L2 9Z"/><path d="M6 11v5c3 2 9 2 12 0v-5"/>',
    'timer' => '<circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2M9 2h6"/>',
    'star' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9L12 3Z"/>',
    'menu' => '<path d="M4 6h16M4 12h16M4 18h10"/>',
    'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    'pen' => '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/>',
    'send' => '<path d="m22 2-7 20-4-9-9-4 20-7Z"/>',
    'mark' => '<path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/>',
    'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    'video' => '<rect x="2" y="6" width="14" height="12" rx="2"/><path d="m22 8-6 4 6 4V8Z"/>',
    'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v5h1"/>',
];
@endphp
<svg width="{{ $size ?? 22 }}" height="{{ $size ?? 22 }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? '' !!}</svg>
