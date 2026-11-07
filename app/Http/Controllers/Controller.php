<?php

namespace App\Http\Controllers;

use App\Models\Month;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

abstract class Controller
{
    /** Resolve "2026-11" to the signed-in user's month (not saved until it has a plan). */
    protected function monthFor(Request $request, string $ym): Month
    {
        [$year, $month] = array_map('intval', explode('-', $ym));
        abort_unless($month >= 1 && $month <= 12 && $year >= 2000 && $year <= 2100, 404);

        return $request->user()->months()->firstOrNew(['year' => $year, 'month' => $month]);
    }

    protected function currentKey(): string
    {
        return Carbon::now()->format('Y-m');
    }

    /** 403 unless the model belongs to the signed-in user. */
    protected function ensureOwner(Request $request, object $model): void
    {
        abort_unless((int) $model->user_id === (int) $request->user()->getKey(), 403);
    }
}
