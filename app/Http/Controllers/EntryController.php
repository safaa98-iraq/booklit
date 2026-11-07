<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Support\Calendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $entry = $request->user()->entries()->create($this->validated($request));

        return redirect()->route('months.show', $entry->spent_on->format('Y-m'))
            ->with('status', 'دُوِّن المصروف في الدفتر.');
    }

    public function show(Request $request, Entry $entry): View
    {
        $this->ensureOwner($request, $entry);
        $entry->load('comments.user');

        return view('entries.show', [
            'entry' => $entry,
            'monthName' => Calendar::monthName($entry->spent_on->month),
            'weekday' => Calendar::WEEKDAYS[$entry->spent_on->dayOfWeek],
        ]);
    }

    public function update(Request $request, Entry $entry): RedirectResponse
    {
        $this->ensureOwner($request, $entry);
        $entry->update($this->validated($request));

        return redirect()->route('entries.show', $entry)->with('status', 'عُدِّل المصروف.');
    }

    public function destroy(Request $request, Entry $entry): RedirectResponse
    {
        $this->ensureOwner($request, $entry);
        $ym = $entry->spent_on->format('Y-m');
        $entry->delete();

        return redirect()->route('months.show', $ym)->with('status', 'حُذف المصروف.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'spent_on' => ['required', 'date', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'category' => MonthController::categoryRule(),
            'description' => ['required', 'string', 'max:160'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
