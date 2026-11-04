@push('head')
    <link rel="stylesheet" href="/vendor/katex/katex.min.css">
@endpush
@push('scripts')
    <script src="/vendor/katex/katex.min.js" defer></script>
    <script>
        window.addEventListener('load', () => {
            if (!window.katex) return; // raw LaTeX stays visible as a fallback
            document.querySelectorAll('[data-latex]').forEach(el => {
                try { katex.render(el.dataset.latex, el, { displayMode: el.dataset.display === '1', throwOnError: false }); } catch (e) {}
            });
        });
    </script>
@endpush
