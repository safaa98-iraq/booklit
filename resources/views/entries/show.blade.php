@extends('layout')
@section('title', $entry->description)

@php use App\Enums\Category; @endphp

@section('content')
    <a class="back" href="{{ route('months.show', $entry->spent_on->format('Y-m')) }}">→ العودة إلى {{ $monthName }}</a>

    <article class="card entry tone-{{ $entry->category->tone() }}">
        <header class="entry__head">
            <span class="entry__kanji" lang="ja">{{ $entry->category->kanji() }}</span>
            <div>
                <h1 class="title">{{ $entry->description }}</h1>
                <p class="muted">{{ $weekday }} {{ $entry->spent_on->day }} {{ $monthName }} {{ $entry->spent_on->year }} · {{ $entry->category->label() }}</p>
            </div>
            <strong class="entry__amount">{{ money($entry->amount) }}</strong>
        </header>
        @if ($entry->note)
            <p class="entry__note">{{ $entry->note }}</p>
        @endif

        <details class="entry__edit" @if ($errors->hasAny(['spent_on', 'category', 'description', 'amount', 'note'])) open @endif>
            <summary>تعديل</summary>
            @if ($errors->hasAny(['spent_on', 'category', 'description', 'amount', 'note']))
                @include('partials.errors')
            @endif
            <form method="POST" action="{{ route('entries.update', $entry) }}" class="form">
                @csrf @method('PUT')
                <label class="field">
                    <span>الوصف</span>
                    <input type="text" name="description" maxlength="160" required value="{{ old('description', $entry->description) }}">
                </label>
                <div class="form__row">
                    <label class="field">
                        <span>المبلغ</span>
                        <input type="number" name="amount" min="0" step="any" inputmode="decimal" required value="{{ old('amount', (float) $entry->amount) }}">
                    </label>
                    <label class="field">
                        <span>التاريخ</span>
                        <input type="date" name="spent_on" required value="{{ old('spent_on', $entry->spent_on->toDateString()) }}">
                    </label>
                </div>
                <fieldset class="field">
                    <legend>القسم</legend>
                    <div class="chips">
                        @foreach (Category::cases() as $cat)
                            <label class="chip tone-{{ $cat->tone() }}">
                                <input type="radio" name="category" value="{{ $cat->value }}" required
                                       @checked(old('category', $entry->category->value) === $cat->value)>
                                <span><b lang="ja">{{ $cat->kanji() }}</b> {{ $cat->label() }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="field">
                    <span>ملاحظة</span>
                    <textarea name="note" rows="3" maxlength="2000">{{ old('note', $entry->note) }}</textarea>
                </label>
                <div class="form__actions">
                    <button class="btn btn--ink" type="submit">حفظ التعديل</button>
                </div>
            </form>
            <form method="POST" action="{{ route('entries.destroy', $entry) }}" data-confirm="حذف هذا المصروف وكل تعليقاته؟" class="danger-zone">
                @csrf @method('DELETE')
                <button class="btn btn--danger" type="submit">حذف المصروف</button>
            </form>
        </details>
    </article>

    <section class="card comments" id="comments">
        <h2 class="section-title"><span lang="ja">言葉</span> التعليقات <span class="muted">({{ $entry->comments->count() }})</span></h2>

        @forelse ($entry->comments as $comment)
            <div class="comment">
                <div class="comment__body">
                    <p>{{ $comment->body }}</p>
                    <small class="muted">{{ $comment->user->name }} · <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time></small>
                </div>
                <form method="POST" action="{{ route('comments.destroy', $comment) }}" data-confirm="حذف هذا التعليق؟">
                    @csrf @method('DELETE')
                    <button type="submit" class="icon-btn" aria-label="حذف التعليق">×</button>
                </form>
            </div>
        @empty
            <p class="muted comments__empty">لا تعليقات بعد. هل كان هذا المصروف ضرورياً؟ هل أسعدك؟</p>
        @endforelse

        @if ($errors->has('body'))
            @include('partials.errors')
        @endif
        <form method="POST" action="{{ route('comments.store', $entry) }}" class="form comment-form">
            @csrf
            <label class="field">
                <span class="sr-only">تعليق جديد</span>
                <textarea name="body" rows="2" maxlength="2000" required placeholder="اكتب خاطرة عن هذا المصروف…">{{ old('body') }}</textarea>
            </label>
            <button class="btn btn--ink" type="submit">أضف تعليقاً</button>
        </form>
    </section>
@endsection
