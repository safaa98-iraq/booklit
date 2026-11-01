@php
    $text = $getBlockText();
    $startPath = $getSiblingStatePath('start_offset');
    $endPath = $getSiblingStatePath('end_offset');
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @if (blank($text))
        <div class="text-sm text-gray-500 dark:text-gray-400">
            اختر كتلة نصية أولاً لعرض نصها هنا، ثم ظلّل الجزء المرتبط بالسؤال.
        </div>
    @else
        <div
            wire:key="anchor-picker-{{ md5($startPath.$text) }}"
            x-data="{
                text: @js($text),
                start: $wire.$entangle(@js($startPath)),
                end: $wire.$entangle(@js($endPath)),
                chars() { return Array.from(this.text) },
                s() { const v = parseInt(this.start); return Number.isFinite(v) ? Math.max(0, v) : 0 },
                e() { const v = parseInt(this.end); return Number.isFinite(v) ? Math.max(this.s(), v) : this.s() },
                part(which) {
                    const c = this.chars();
                    if (which === 'before') return c.slice(0, this.s()).join('');
                    if (which === 'selected') return c.slice(this.s(), this.e()).join('');
                    return c.slice(this.e()).join('');
                },
                capture() {
                    const sel = window.getSelection();
                    if (! sel || ! sel.rangeCount) return;
                    const range = sel.getRangeAt(0);
                    const root = this.$refs.source;
                    if (! root.contains(range.startContainer) || ! root.contains(range.endContainer)) return;
                    const selected = range.toString();
                    if (! selected.length) return;
                    const pre = document.createRange();
                    pre.selectNodeContents(root);
                    pre.setEnd(range.startContainer, range.startOffset);
                    // DOM offsets are UTF-16 units; anchors use code points.
                    const startCp = Array.from(pre.toString()).length;
                    this.start = startCp;
                    this.end = startCp + Array.from(selected).length;
                    sel.removeAllRanges();
                },
                clear() { this.start = null; this.end = null },
            }"
            class="space-y-2"
        >
            <div
                x-ref="source"
                x-on:mouseup="capture()"
                x-on:touchend="capture()"
                dir="rtl"
                class="cursor-text select-text whitespace-pre-wrap rounded-lg border border-gray-300 bg-white p-3 text-base leading-loose dark:border-white/10 dark:bg-white/5"
            ><span x-text="part('before')"></span><mark class="rounded bg-yellow-200 px-0.5 dark:bg-yellow-500/40" x-text="part('selected')"></mark><span x-text="part('after')"></span></div>

            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>ظلّل النص بالفأرة لتحديد المقطع. سيتم تمييزه للطالب عند إظهار الإجابة.</span>
                <button type="button" x-on:click="clear()" class="text-danger-600 hover:underline">مسح التحديد</button>
            </div>
        </div>
    @endif
</x-dynamic-component>
