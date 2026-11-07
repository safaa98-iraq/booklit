<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Models\Entry;
use App\Support\Calendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MonthController extends Controller
{
    public function home(): RedirectResponse
    {
        return redirect()->route('months.show', $this->currentKey());
    }

    /** The year at a glance: one tile per month. */
    public function index(Request $request): View
    {
        $year = (int) $request->integer('year', (int) now()->year);
        abort_unless($year >= 2000 && $year <= 2100, 404);

        $user = $request->user();
        $plans = $user->months()->where('year', $year)->get()->keyBy('month');

        $spentByMonth = $user->entries()
            ->whereBetween('spent_on', ["$year-01-01", "$year-12-31"])
            ->get(['spent_on', 'amount'])
            ->groupBy(fn (Entry $e) => $e->spent_on->month)
            ->map(fn ($group) => (float) $group->sum('amount'));

        $months = collect(range(1, 12))->map(function (int $m) use ($year, $plans, $spentByMonth) {
            $plan = $plans->get($m);
            $spent = $spentByMonth->get($m, 0.0);
            $income = (float) ($plan->income ?? 0);
            $fixed = (float) ($plan->fixed_costs ?? 0);

            return (object) [
                'key' => sprintf('%04d-%02d', $year, $m),
                'name' => Calendar::monthName($m),
                'wafu' => Calendar::wafu($m),
                'income' => $income,
                'spent' => $spent,
                'saved' => $plan ? $income - $fixed - $spent : null,
                'has_plan' => (bool) $plan,
                'is_current' => $year === now()->year && $m === now()->month,
            ];
        });

        return view('months.index', [
            'year' => $year,
            'months' => $months,
            'totalSpent' => $months->sum('spent'),
            'totalSaved' => $months->whereNotNull('saved')->sum('saved'),
        ]);
    }

    public function show(Request $request, string $ym): View
    {
        $month = $this->monthFor($request, $ym);
        $filter = Category::tryFrom((string) $request->query('category'));

        $all = $month->entries()->withCount('comments')
            ->orderByDesc('spent_on')->orderByDesc('id')->get();

        $byCategory = collect(Category::cases())->mapWithKeys(
            fn (Category $c) => [$c->value => (float) $all->where('category', $c)->sum('amount')]
        );

        $weeks = $all->groupBy(fn (Entry $e) => $e->week())->map(
            fn ($group) => collect(Category::cases())->mapWithKeys(
                fn (Category $c) => [$c->value => (float) $group->where('category', $c)->sum('amount')]
            )
        );

        $spent = (float) $all->sum('amount');
        $available = $month->available();
        $start = $month->start();
        $today = Carbon::today();

        return view('months.show', [
            'month' => $month,
            'entries' => $filter ? $all->where('category', $filter) : $all,
            'days' => ($filter ? $all->where('category', $filter) : $all)
                ->groupBy(fn (Entry $e) => $e->spent_on->toDateString()),
            'filter' => $filter,
            'byCategory' => $byCategory,
            'weeks' => $weeks,
            'weekCount' => intdiv($start->daysInMonth - 1, 7) + 1,
            'spent' => $spent,
            'available' => $available,
            'remaining' => $available - $spent,
            'usage' => $available > 0 ? min(100, round($spent / $available * 100)) : ($spent > 0 ? 100 : 0),
            'prev' => $start->copy()->subMonth()->format('Y-m'),
            'next' => $start->copy()->addMonth()->format('Y-m'),
            'defaultDate' => $today->isSameMonth($start) ? $today->toDateString() : $start->toDateString(),
            'monthName' => Calendar::monthName($month->month),
            'wafu' => Calendar::wafu($month->month),
        ]);
    }

    public function updatePlan(Request $request, string $ym): RedirectResponse
    {
        $month = $this->monthFor($request, $ym);
        $month->fill($request->validate([
            'income' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'fixed_costs' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'savings_goal' => ['required', 'numeric', 'min:0', 'max:999999999999'],
            'intention' => ['nullable', 'string', 'max:2000'],
        ]))->save();

        return redirect()->route('months.show', $ym)->with('status', 'حُفظت خطة الشهر.');
    }

    public function updateReflection(Request $request, string $ym): RedirectResponse
    {
        $month = $this->monthFor($request, $ym);
        $month->fill($request->validate([
            'reflection' => ['nullable', 'string', 'max:4000'],
            'improvement' => ['nullable', 'string', 'max:4000'],
        ]))->save();

        return redirect()->to(route('months.show', $ym).'#reflection')->with('status', 'حُفظ تأمّل الشهر.');
    }

    public static function categoryRule(): array
    {
        return ['required', Rule::enum(Category::class)];
    }
}
