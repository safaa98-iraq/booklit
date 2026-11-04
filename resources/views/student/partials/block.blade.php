{{-- One content block. $ranges: highlight ranges for this block (passage anchor + the student's own). --}}
@php
    use App\Support\Highlighter;
    use App\Support\BlockText;
    $d = $block->data ?? [];
    $ranges = $ranges ?? [];
    $hasAnchor = collect($ranges)->contains('class', 'anchor');
@endphp
<div class="block {{ $hasAnchor ? 'is-anchor' : '' }}" id="b-{{ $block->uuid }}" data-block="{{ $block->uuid }}">
    @switch($block->type->value)
        @case('heading')
            @php($level = in_array((int) ($d['level'] ?? 2), [2, 3, 4]) ? (int) $d['level'] : 2)
            <h{{ $level }} data-base="0">{{ Highlighter::render($block->plain_text, 0, $ranges) }}</h{{ $level }}>
            @break
        @case('list')
            @php($items = array_values($d['items'] ?? []))
            @php($offsets = Highlighter::offsets($items))
            <{{ ($d['style'] ?? '') === 'ordered' ? 'ol' : 'ul' }}>
                @foreach ($items as $i => $item)
                    <li data-base="{{ $offsets[$i] }}">{{ Highlighter::render((string) $item, $offsets[$i], $ranges) }}</li>
                @endforeach
            </{{ ($d['style'] ?? '') === 'ordered' ? 'ol' : 'ul' }}>
            @break
        @case('table')
            @php($rows = array_values($d['rows'] ?? []))
            @php($rowOffsets = Highlighter::offsets(array_map(fn ($r) => implode(BlockText::CELL_SEPARATOR, (array) $r), $rows)))
            <div class="table-wrap"><table>
                @foreach ($rows as $r => $cells)
                    @php($cellOffsets = Highlighter::offsets((array) $cells, mb_strlen(BlockText::CELL_SEPARATOR)))
                    @php($tag = $r === 0 && ($d['header'] ?? true) ? 'th' : 'td')
                    <tr>
                        @foreach ((array) $cells as $c => $cell)
                            @php($base = $rowOffsets[$r] + $cellOffsets[$c])
                            <{{ $tag }} data-base="{{ $base }}">{{ Highlighter::render((string) $cell, $base, $ranges) }}</{{ $tag }}>
                        @endforeach
                    </tr>
                @endforeach
            </table></div>
            @break
        @case('quote')
            @php($qc = ['warning' => '#D97706', 'tip' => '#493BEA', 'definition' => '#2452DB'][$d['variant'] ?? ''] ?? '#A66BFA')
            <div class="note" style="--qc: {{ $qc }}" data-base="0">{{ Highlighter::render($block->plain_text, 0, $ranges) }}</div>
            @break
        @case('math')
            <div class="math" data-latex="{{ $d['latex'] ?? '' }}" data-display="{{ ($d['display'] ?? true) ? '1' : '0' }}">{{ $d['latex'] ?? '' }}</div>
            @break
        @case('image')
            <figure>
                @if (! empty($d['path']))<img src="{{ \App\Support\Media::url($d['path']) }}" alt="{{ $d['alt'] ?? '' }}" loading="lazy">@endif
                @if (! empty($d['caption']))<figcaption>{{ $d['caption'] }}</figcaption>@endif
            </figure>
            @break
        @default
            <p data-base="0">{{ Highlighter::render($block->plain_text, 0, $ranges) }}</p>
    @endswitch
</div>
