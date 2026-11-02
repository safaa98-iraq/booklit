{{-- One content block. $hl = ['start','end'] when this block holds the highlighted passage. --}}
@php
    use App\Support\Highlighter;
    $d = $block->data ?? [];
    $hl = $hl ?? null;
@endphp
<div class="block {{ $hl ? 'is-anchor' : '' }}" id="b-{{ $block->uuid }}">
    @switch($block->type->value)
        @case('heading')
            @php($level = in_array((int) ($d['level'] ?? 2), [2, 3, 4]) ? (int) $d['level'] : 2)
            <h{{ $level }}>{{ Highlighter::render($block->plain_text, 0, $hl) }}</h{{ $level }}>
            @break
        @case('list')
            @php($items = array_values($d['items'] ?? []))
            @php($offsets = Highlighter::listOffsets($items))
            <{{ ($d['style'] ?? '') === 'ordered' ? 'ol' : 'ul' }}>
                @foreach ($items as $i => $item)
                    <li>{{ Highlighter::render((string) $item, $offsets[$i], $hl) }}</li>
                @endforeach
            </{{ ($d['style'] ?? '') === 'ordered' ? 'ol' : 'ul' }}>
            @break
        @case('quote')
            @php($qc = ['warning' => '#ea580c', 'tip' => '#0d9488', 'definition' => '#4f46e5'][$d['variant'] ?? ''] ?? '#64748b')
            <div class="note" style="--qc: {{ $qc }}">{{ Highlighter::render($block->plain_text, 0, $hl) }}</div>
            @break
        @case('math')
            <div class="math" data-latex="{{ $d['latex'] ?? '' }}" data-display="{{ ($d['display'] ?? true) ? '1' : '0' }}">{{ $d['latex'] ?? '' }}</div>
            @break
        @case('image')
            <figure>
                @if (! empty($d['path']))
                    <img src="{{ \App\Support\Media::url($d['path']) }}" alt="{{ $d['alt'] ?? '' }}" loading="lazy">
                @endif
                @if (! empty($d['caption']))
                    <figcaption>{{ Highlighter::render($block->plain_text, 0, $hl) }}</figcaption>
                @endif
            </figure>
            @break
        @default
            <p>{{ Highlighter::render($block->plain_text, 0, $hl) }}</p>
    @endswitch
</div>
