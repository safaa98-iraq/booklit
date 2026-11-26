{{-- Built-in calculator: a floating button on every page. --}}
<button type="button" class="calc-fab" data-calc-open aria-label="الحاسبة" title="الحاسبة">
    <span aria-hidden="true">🧮</span>
</button>

<div class="calc" data-calc hidden role="dialog" aria-modal="false" aria-labelledby="calc-title">
    <header class="calc__head">
        <h2 id="calc-title"><span lang="ja">電卓</span> الحاسبة</h2>
        <button type="button" class="icon-btn" data-calc-close aria-label="إغلاق الحاسبة">×</button>
    </header>

    <div class="calc__screen" dir="ltr">
        <div class="calc__expr" data-calc-expr>&nbsp;</div>
        <output class="calc__value" data-calc-value>0</output>
    </div>

    <div class="calc__keys" dir="ltr">
        <button type="button" class="calc__key calc__key--soft" data-key="C">C</button>
        <button type="button" class="calc__key calc__key--soft" data-key="(">(</button>
        <button type="button" class="calc__key calc__key--soft" data-key=")">)</button>
        <button type="button" class="calc__key calc__key--op" data-key="/" aria-label="قسمة">÷</button>

        <button type="button" class="calc__key" data-key="7">7</button>
        <button type="button" class="calc__key" data-key="8">8</button>
        <button type="button" class="calc__key" data-key="9">9</button>
        <button type="button" class="calc__key calc__key--op" data-key="*" aria-label="ضرب">×</button>

        <button type="button" class="calc__key" data-key="4">4</button>
        <button type="button" class="calc__key" data-key="5">5</button>
        <button type="button" class="calc__key" data-key="6">6</button>
        <button type="button" class="calc__key calc__key--op" data-key="-" aria-label="طرح">−</button>

        <button type="button" class="calc__key" data-key="1">1</button>
        <button type="button" class="calc__key" data-key="2">2</button>
        <button type="button" class="calc__key" data-key="3">3</button>
        <button type="button" class="calc__key calc__key--op" data-key="+" aria-label="جمع">+</button>

        <button type="button" class="calc__key calc__key--soft" data-key="%">%</button>
        <button type="button" class="calc__key" data-key="0">0</button>
        <button type="button" class="calc__key" data-key="000">000</button>
        <button type="button" class="calc__key" data-key=".">.</button>

        <button type="button" class="calc__key calc__key--soft" data-key="Backspace" aria-label="حذف آخر رقم">⌫</button>
        <button type="button" class="calc__key calc__key--eq" data-key="=">=</button>
    </div>

    <button type="button" class="btn btn--ink calc__use" data-calc-use hidden>استخدم الناتج في خانة المبلغ</button>
</div>
