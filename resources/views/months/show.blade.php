@extends('layout')
@section('title', $monthName.' '.$month->year)

@php use App\Enums\Category; @endphp

@section('content')
    {{-- ───────── Month heading ───────── --}}
    <section class="month-head">
        <a class="month-head__nav" href="{{ route('months.show', $prev) }}" aria-label="الشهر السابق">→</a>
        <div class="month-head__title">
            <span class="month-head__wafu" lang="ja" aria-hidden="true">{{ $wafu }}</span>
            <h1>{{ $monthName }} <span class="muted">{{ $month->year }}</span></h1>
        </div>
        <a class="month-head__nav" href="{{ route('months.show', $next) }}" aria-label="الشهر التالي">←</a>
    </section>

    {{-- ───────── Overview: ensō + numbers ───────── --}}
    <section class="overview card">
        <div class="overview__ring">
            @include('partials.enso', ['percent' => $usage])
            <div class="overview__center">
                <small>{{ $remaining >= 0 ? 'المتبقّي' : 'تجاوزت بـ' }}</small>
                <strong @class(['is-over' => $remaining < 0])>{{ money(abs($remaining), false) }}</strong>
                <small>{{ auth()->user()->currency }}</small>
            </div>
        </div>
        <dl class="overview__stats">
            <div><dt>الدخل</dt><dd>{{ money($month->income) }}</dd></div>
            <div><dt>المصاريف الثابتة</dt><dd>{{ money($month->fixed_costs) }}</dd></div>
            <div><dt>هدف الادّخار</dt><dd>{{ money($month->savings_goal) }}</dd></div>
            <div class="is-key"><dt>المتاح للإنفاق</dt><dd>{{ money($available) }}</dd></div>
            <div class="is-key"><dt>أنفقت</dt><dd>{{ money($spent) }}</dd></div>
        </dl>
    </section>

    {{-- ───────── Monthly plan (the first two kakeibo questions) ───────── --}}
    <details class="card plan" @if (! $month->exists || $errors->hasAny(['income', 'fixed_costs', 'savings_goal'])) open @endif>
        <summary>
            <span class="kanji-tag" lang="ja">予算</span>
            <span>خطة الشهر</span>
            @unless ($month->exists)<em class="badge">لم تُكتب بعد</em>@endunless
        </summary>
        <form method="POST" action="{{ route('months.plan', $month->key()) }}" class="form form--grid">
            @csrf @method('PUT')
            <label class="field">
                <span>كم لديك من المال؟ <small>(الدخل)</small></span>
                <input type="number" name="income" min="0" step="any" inputmode="decimal" required
                       value="{{ old('income', $month->exists ? (float) $month->income : '') }}">
            </label>
            <label class="field">
                <span>المصاريف الثابتة <small>(إيجار، اشتراكات…)</small></span>
                <input type="number" name="fixed_costs" min="0" step="any" inputmode="decimal" required
                       value="{{ old('fixed_costs', $month->exists ? (float) $month->fixed_costs : 0) }}">
            </label>
            <label class="field">
                <span>كم تريد أن تدّخر؟</span>
                <input type="number" name="savings_goal" min="0" step="any" inputmode="decimal" required
                       value="{{ old('savings_goal', $month->exists ? (float) $month->savings_goal : 0) }}">
            </label>
            <label class="field field--wide">
                <span>نيّة الشهر <small>(اختياري)</small></span>
                <textarea name="intention" rows="2" placeholder="مثلاً: أطبخ في البيت أكثر، وأشتري كتاباً واحداً فقط.">{{ old('intention', $month->intention) }}</textarea>
            </label>
            <div class="form__actions field--wide">
                <button class="btn btn--ink" type="submit">حفظ الخطة</button>
            </div>
        </form>
    </details>

    @if ($month->intention)
        <blockquote class="intention"><span lang="ja" aria-hidden="true">志</span>{{ $month->intention }}</blockquote>
    @endif

    {{-- ───────── The four pillars ───────── --}}
    <section class="pillars" aria-label="الأقسام الأربعة">
        @foreach (Category::cases() as $cat)
            @php $total = $byCategory[$cat->value]; @endphp
            <a href="{{ $filter === $cat ? route('months.show', $month->key()) : route('months.show', [$month->key(), 'category' => $cat->value]) }}"
               @class(['pillar', 'tone-'.$cat->tone(), 'is-active' => $filter === $cat, 'is-dim' => $filter && $filter !== $cat])>
                <span class="pillar__kanji" lang="ja">{{ $cat->kanji() }}</span>
                <span class="pillar__label">{{ $cat->label() }}</span>
                <strong class="pillar__sum">{{ money($total, false) }}</strong>
                <span class="pillar__bar"><span style="width: {{ $spent > 0 ? round($total / $spent * 100) : 0 }}%"></span></span>
                <small class="pillar__share">{{ $spent > 0 ? round($total / $spent * 100) : 0 }}٪ من المصروف</small>
            </a>
        @endforeach
    </section>

    <div class="split">
        {{-- ───────── Add an entry ───────── --}}
        <section class="card add" id="add">
            <h2 class="section-title"><span lang="ja">記入</span> تدوين مصروف</h2>
            @if ($errors->hasAny(['spent_on', 'category', 'description', 'amount', 'note']))
                @include('partials.errors')
            @endif
            <form method="POST" action="{{ route('entries.store') }}" class="form">
                @csrf
                <label class="field">
                    <span>الوصف</span>
                    <input type="text" name="description" maxlength="160" required value="{{ old('description') }}" placeholder="خبز وخضار">
                </label>
                <div class="form__row">
                    <label class="field">
                        <span>المبلغ</span>
                        <input type="number" name="amount" min="0" step="any" inputmode="decimal" required value="{{ old('amount') }}">
                    </label>
                    <label class="field">
                        <span>التاريخ</span>
                        <input type="date" name="spent_on" required value="{{ old('spent_on', $defaultDate) }}">
                    </label>
                </div>
                <fieldset class="field">
                    <legend>القسم</legend>
                    <div class="chips">
                        @foreach (Category::cases() as $cat)
                            <label class="chip tone-{{ $cat->tone() }}" title="{{ $cat->hint() }}">
                                <input type="radio" name="category" value="{{ $cat->value }}" required
                                       @checked(old('category', $filter?->value ?? 'needs') === $cat->value)>
                                <span><b lang="ja">{{ $cat->kanji() }}</b> {{ $cat->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="field">
                    <span>ملاحظة <small>(اختياري)</small></span>
                    <textarea name="note" rows="2" maxlength="2000">{{ old('note') }}</textarea>
                </label>
                <button class="btn btn--ink" type="submit">دوِّن</button>
            </form>
        </section>

        {{-- ───────── Weekly view ───────── --}}
        <section class="card weeks">
            <h2 class="section-title"><span lang="ja">週</span> الأسابيع</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">الأسبوع</th>
                            @foreach (Category::cases() as $cat)
                                <th scope="col" class="tone-{{ $cat->tone() }}"><span lang="ja">{{ $cat->kanji() }}</span></th>
                            @endforeach
                            <th scope="col">المجموع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($w = 1; $w <= $weekCount; $w++)
                            @php $row = $weeks->get($w); @endphp
                            <tr>
                                <th scope="row">{{ $w }}</th>
                                @foreach (Category::cases() as $cat)
                                    <td>{{ $row && $row[$cat->value] > 0 ? money($row[$cat->value], false) : '·' }}</td>
                                @endforeach
                                <td class="is-strong">{{ $row ? money($row->sum(), false) : '·' }}</td>
                            </tr>
                        @endfor
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row">الشهر</th>
                            @foreach (Category::cases() as $cat)
                                <td>{{ money($byCategory[$cat->value], false) }}</td>
                            @endforeach
                            <td class="is-strong">{{ money($spent, false) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>

    {{-- ───────── The ledger ───────── --}}
    <section class="card ledger">
        <header class="ledger__head">
            <h2 class="section-title"><span lang="ja">帳</span> المصاريف
                @if ($filter)
                    <span class="badge tone-{{ $filter->tone() }}">{{ $filter->label() }}</span>
                @endif
            </h2>
            @if ($filter)
                <a class="link" href="{{ route('months.show', $month->key()) }}">عرض الكل</a>
            @endif
        </header>

        @forelse ($days as $date => $items)
            @php $d = \Illuminate\Support\Carbon::parse($date); @endphp
            <div class="day">
                <div class="day__date">
                    <strong>{{ $d->day }}</strong>
                    <span>{{ \App\Support\Calendar::WEEKDAYS[$d->dayOfWeek] }}</span>
                </div>
                <ul class="day__list">
                    @foreach ($items as $entry)
                        <li class="row">
                            <span class="row__tag tone-{{ $entry->category->tone() }}" lang="ja" title="{{ $entry->category->label() }}">{{ $entry->category->kanji() }}</span>
                            <a class="row__main" href="{{ route('entries.show', $entry) }}">
                                <span class="row__desc">{{ $entry->description }}</span>
                                @if ($entry->note)
                                    <span class="row__note">{{ \Illuminate\Support\Str::limit($entry->note, 70) }}</span>
                                @endif
                            </a>
                            @if ($entry->comments_count)
                                <a class="row__comments" href="{{ route('entries.show', $entry) }}#comments" title="التعليقات">✎ {{ $entry->comments_count }}</a>
                            @endif
                            <span class="row__amount">{{ money($entry->amount, false) }}</span>
                            <form method="POST" action="{{ route('entries.destroy', $entry) }}" data-confirm="حذف «{{ $entry->description }}»؟">
                                @csrf @method('DELETE')
                                <button type="submit" class="icon-btn" aria-label="حذف {{ $entry->description }}">×</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="empty">
                <span lang="ja" aria-hidden="true">空</span>
                <p>{{ $filter ? 'لا مصاريف في هذا القسم بعد.' : 'الصفحة بيضاء بعد. دوِّن أول مصروف لهذا الشهر.' }}</p>
            </div>
        @endforelse
    </section>

    {{-- ───────── End-of-month reflection: the four questions ───────── --}}
    <section class="card reflection" id="reflection">
        <h2 class="section-title"><span lang="ja">反省</span> تأمّل الشهر</h2>
        <ol class="questions">
            <li><span>كم كان لديك؟</span><strong>{{ money($month->income) }}</strong></li>
            <li><span>كم أردت أن تدّخر؟</span><strong>{{ money($month->savings_goal) }}</strong></li>
            <li><span>كم أنفقت؟</span><strong>{{ money((float) $month->fixed_costs + $spent) }}</strong></li>
            <li>
                <span>كم ادّخرت فعلاً؟</span>
                @php $saved = (float) $month->income - (float) $month->fixed_costs - $spent; @endphp
                <strong @class(['is-good' => $month->exists && $saved >= (float) $month->savings_goal, 'is-over' => $saved < 0])>{{ money($saved) }}</strong>
            </li>
        </ol>
        <form method="POST" action="{{ route('months.reflection', $month->key()) }}" class="form">
            @csrf @method('PUT')
            <label class="field">
                <span>كيف مضى الشهر؟</span>
                <textarea name="reflection" rows="3" maxlength="4000">{{ old('reflection', $month->reflection) }}</textarea>
            </label>
            <label class="field">
                <span>كيف أتحسّن في الشهر القادم؟</span>
                <textarea name="improvement" rows="3" maxlength="4000">{{ old('improvement', $month->improvement) }}</textarea>
            </label>
            <button class="btn btn--ghost" type="submit">حفظ التأمّل</button>
        </form>
    </section>
@endsection
