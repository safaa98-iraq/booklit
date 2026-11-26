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

/* ───────── Drafts: whatever you type is kept on this phone until it is saved ───────── */
(function () {
    'use strict';

    var store = {
        get: function (k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
        set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
        del: function (k) { try { localStorage.removeItem(k); } catch (e) {} }
    };
    var session = {
        get: function (k) { try { return JSON.parse(sessionStorage.getItem(k)); } catch (e) { return null; } },
        set: function (k, v) { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
    };

    var PREFIX = 'kakeibo:draft:';
    var saved = document.querySelector('.toast');                 // the server confirmed a save
    var submitted = session.get('kakeibo:submitted') || [];
    if (saved) { submitted.forEach(function (k) { store.del(PREFIX + k); }); }
    session.set('kakeibo:submitted', []);

    function fields(form) {
        return Array.prototype.filter.call(form.elements, function (el) {
            return el.name && el.name.charAt(0) !== '_' && el.type !== 'file' && el.type !== 'hidden'
                && el.type !== 'submit' && el.type !== 'button';
        });
    }

    function snapshot(form) {
        var data = {};
        fields(form).forEach(function (el) {
            if (el.type === 'radio' || el.type === 'checkbox') { if (el.checked) data[el.name] = el.value; }
            else data[el.name] = el.value;
        });
        return data;
    }

    function isWorthKeeping(form, data) {
        // Only text the person actually typed counts (not the default date or category).
        return fields(form).some(function (el) {
            return (el.tagName === 'TEXTAREA' || /^(text|number|search)$/.test(el.type)) && data[el.name] && data[el.name] !== el.defaultValue;
        });
    }

    document.querySelectorAll('form[data-draft]').forEach(function (form) {
        var key = PREFIX + form.dataset.draft;
        var draft = store.get(key);
        var hasErrors = form.closest('.card') && form.closest('.card').querySelector('.alert');

        if (draft && draft.data && !hasErrors) {
            fields(form).forEach(function (el) {
                if (!(el.name in draft.data)) return;
                if (el.type === 'radio' || el.type === 'checkbox') el.checked = el.value === draft.data[el.name];
                else el.value = draft.data[el.name];
            });
            var note = document.createElement('p');
            note.className = 'draft-note';
            note.innerHTML = '<span>✎ أُعيدت مسودتك التي لم تُحفظ بعد.</span> <button type="button">تجاهلها</button>';
            note.querySelector('button').addEventListener('click', function () {
                store.del(key); form.reset(); note.remove();
                form.querySelectorAll('[data-amount]').forEach(function (i) { i.dispatchEvent(new Event('input', { bubbles: true })); });
            });
            form.insertBefore(note, form.firstChild);
            form.querySelectorAll('[data-amount]').forEach(function (i) { i.dispatchEvent(new Event('input', { bubbles: true })); });
            var details = form.closest('details');
            if (details) details.open = true;
        }

        var timer;
        var save = function () {
            clearTimeout(timer);
            timer = setTimeout(function () {
                var data = snapshot(form);
                if (isWorthKeeping(form, data)) store.set(key, { at: Date.now(), data: data });
                else store.del(key);
            }, 250);
        };
        form.addEventListener('input', save);
        form.addEventListener('change', save);
        form.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return;
            var list = session.get('kakeibo:submitted') || [];
            list.push(form.dataset.draft);
            session.set('kakeibo:submitted', list);
        });
    });

    /* ───────── Gentle weekly backup reminder ───────── */

    var now = Date.now(), WEEK = 7 * 864e5;
    var since = store.get('kakeibo:lastBackup') || store.get('kakeibo:firstSeen');
    if (!since) store.set('kakeibo:firstSeen', now);
    var nudge = document.querySelector('[data-backup-nudge]');
    var snoozed = store.get('kakeibo:backupSnooze');
    if (nudge && since && now - since > WEEK && !(snoozed && now < snoozed)) nudge.hidden = false;

    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-backup-link], a[href$="/backup"]')) {
            store.set('kakeibo:lastBackup', Date.now());
            if (nudge) nudge.hidden = true;
        }
        if (e.target.closest('[data-backup-dismiss]')) {
            store.set('kakeibo:backupSnooze', Date.now() + 3 * 864e5);
            nudge.hidden = true;
        }
    });

    /* ───────── ＋ in the tab bar: straight to the expense form ───────── */

    function focusAdd() {
        var add = document.getElementById('add');
        if (!add) return;
        add.scrollIntoView({ behavior: 'smooth', block: 'start' });
        var input = add.querySelector('input[name="description"]');
        if (input) setTimeout(function () { input.focus({ preventScroll: true }); }, 350);
    }
    if (location.hash === '#add') focusAdd();
    document.addEventListener('click', function (e) {
        var go = e.target.closest('[data-go-add]');
        if (go && document.getElementById('add')) { e.preventDefault(); history.replaceState(null, '', '#add'); focusAdd(); }
    });
})();
