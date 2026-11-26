/* 家計簿 — calculator and live "remaining" preview. No dependencies. */
(function () {
    'use strict';

    var nf = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 });
    var fmt = function (n) { return nf.format(n); };

    /* ───────── Live preview: remaining after the amount being typed ───────── */

    var preview = document.querySelector('[data-preview]');
    if (preview && preview.dataset.hasPlan === '1') {
        var form = preview.closest('form');
        var input = form.querySelector('[data-amount]');
        var after = preview.querySelector('[data-preview-after]');
        var base = parseFloat(preview.dataset.remaining) || 0;
        var currency = preview.dataset.currency || '';

        var update = function () {
            var amount = parseFloat(input.value) || 0;
            var left = base - amount;
            after.textContent = (left < 0 ? '−' : '') + fmt(Math.abs(left)) + ' ' + currency;
            preview.classList.toggle('is-over', left < 0);
            preview.classList.toggle('is-active', amount > 0);
        };
        input.addEventListener('input', update);
        update();
    }

    /* ───────── Calculator ───────── */

    var calc = document.querySelector('[data-calc]');
    if (!calc) return;

    var exprEl = calc.querySelector('[data-calc-expr]');
    var valueEl = calc.querySelector('[data-calc-value]');
    var useBtn = calc.querySelector('[data-calc-use]');
    var expr = '';
    var justEvaluated = false;
    var target = null;          // amount input the result will go into

    // Safe evaluator: numbers, + − × ÷, %, parentheses. No eval().
    function evaluate(src) {
        var tokens = src.match(/\d+(?:\.\d*)?|\.\d+|[-+*/%()]/g) || [];
        var pos = 0;
        var peek = function () { return tokens[pos]; };
        var next = function () { return tokens[pos++]; };

        function primary() {
            var t = next();
            if (t === '-') return -primary();
            if (t === '+') return primary();
            if (t === '(') {
                var v = sum();
                if (peek() === ')') next();
                return v;
            }
            if (t === undefined || isNaN(parseFloat(t))) throw new Error('syntax');
            var n = parseFloat(t);
            while (peek() === '%') { next(); n = n / 100; }
            return n;
        }
        function product() {
            var v = primary();
            while (peek() === '*' || peek() === '/') {
                var op = next(), r = primary();
                if (op === '/' && r === 0) throw new Error('div0');
                v = op === '*' ? v * r : v / r;
            }
            return v;
        }
        function sum() {
            var v = product();
            while (peek() === '+' || peek() === '-') {
                var op = next(), r = product();
                v = op === '+' ? v + r : v - r;
            }
            return v;
        }

        var result = sum();
        if (pos < tokens.length || !isFinite(result)) throw new Error('syntax');
        return Math.round(result * 100) / 100;
    }

    function pretty(s) {
        return s.replace(/\*/g, ' × ').replace(/\//g, ' ÷ ').replace(/\+/g, ' + ').replace(/(\d|\)|%)-/g, '$1 − ')
                .replace(/\d+(?:\.\d*)?/g, function (n) {
                    var parts = n.split('.');
                    return Number(parts[0]).toLocaleString('en-US') + (parts.length > 1 ? '.' + parts[1] : '');
                });
    }

    function render() {
        exprEl.textContent = expr ? pretty(expr) : ' ';
        if (!expr) { valueEl.textContent = '0'; valueEl.classList.remove('is-error'); return; }
        try {
            valueEl.textContent = fmt(evaluate(expr));
            valueEl.classList.remove('is-error');
        } catch (e) {
            // Unfinished expression (e.g. "12+"): keep the last good value quietly.
        }
    }

    function press(key) {
        var ops = '+-*/';
        if (key === 'C') { expr = ''; justEvaluated = false; return render(); }
        if (key === 'Backspace') { expr = expr.slice(0, -1); justEvaluated = false; return render(); }
        if (key === '=') {
            if (!expr) return;
            try {
                var r = evaluate(expr);
                exprEl.textContent = pretty(expr) + ' =';
                expr = String(r);
                valueEl.textContent = fmt(r);
                valueEl.classList.remove('is-error');
                justEvaluated = true;
            } catch (e) {
                valueEl.textContent = e.message === 'div0' ? 'لا قسمة على صفر' : 'خطأ';
                valueEl.classList.add('is-error');
            }
            return;
        }
        if (justEvaluated && /[\d.(]/.test(key)) expr = '';     // start fresh after "="
        justEvaluated = false;

        var last = expr.slice(-1);
        if (ops.indexOf(key) !== -1 && ops.indexOf(last) !== -1 && !(key === '-' && last !== '-')) {
            expr = expr.slice(0, -1);                            // replace the previous operator
        }
        if (key === '.') {
            var lastNumber = expr.split(/[-+*/%()]/).pop();
            if (lastNumber.indexOf('.') !== -1) return;
            if (lastNumber === '') key = '0.';
        }
        if (expr.length > 60) return;
        expr += key;
        render();
    }

    function open(fromTrigger) {
        // A trigger inside a form fills that form's amount; the floating button fills the page's first one.
        var scope = fromTrigger && fromTrigger.closest('form');
        target = (scope && scope.querySelector('[data-amount]')) || document.querySelector('[data-amount]');
        useBtn.hidden = !target;

        if (target && target.value && !expr) { expr = String(parseFloat(target.value) || ''); }
        calc.hidden = false;
        document.body.classList.add('calc-open');
        render();
        calc.querySelector('[data-key="="]').focus({ preventScroll: true });
    }

    function close() {
        calc.hidden = true;
        document.body.classList.remove('calc-open');
    }

    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-calc-open]');
        if (opener) { calc.hidden ? open(opener) : close(); return; }
        if (e.target.closest('[data-calc-close]')) return close();
        var k = e.target.closest('[data-key]');
        if (k && calc.contains(k)) return press(k.dataset.key);
        if (e.target.closest('[data-calc-use]') && target) {
            try { var r = evaluate(expr || '0'); } catch (err) { return; }
            if (r <= 0) { valueEl.textContent = 'المبلغ يجب أن يكون أكبر من صفر'; valueEl.classList.add('is-error'); return; }
            target.value = r;
            target.dispatchEvent(new Event('input', { bubbles: true }));
            close();
            target.focus();
        }
    });

    // Keyboard while the calculator is open (Arabic-Indic digits too).
    document.addEventListener('keydown', function (e) {
        if (calc.hidden || e.ctrlKey || e.metaKey || e.altKey) return;
        var el = e.target;
        if (el && el.matches && el.matches('input, textarea, select') && !calc.contains(el)) return;
        var k = e.key.replace(/[٠-٩]/g, function (d) { return String(d.charCodeAt(0) - 0x0660); });
        var map = { 'Enter': '=', '×': '*', 'x': '*', '÷': '/', ',': '.', '٫': '.', 'Delete': 'C', 'c': 'C' };
        k = map[k] || k;
        if (k === 'Escape') { e.preventDefault(); return close(); }
        if (/^[\d.+\-*/%()=C]$/.test(k) || k === 'Backspace') {
            e.preventDefault();
            press(k);
        }
    });
})();
