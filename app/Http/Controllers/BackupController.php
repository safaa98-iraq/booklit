<?php

namespace App\Http\Controllers;

use App\Enums\Category;
use App\Models\Comment;
use App\Models\Entry;
use App\Models\Month;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Download the whole notebook as a file, and restore it from one. */
class BackupController extends Controller
{
    private const FORMAT = 'kakeibo-backup';

    public function download(Request $request)
    {
        $user = $request->user();

        $data = [
            'format' => self::FORMAT,
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'settings' => ['name' => $user->name, 'currency' => $user->currency],
            'months' => $user->months()->orderBy('year')->orderBy('month')->get()->map(fn (Month $m) => [
                'year' => $m->year,
                'month' => $m->month,
                'income' => (float) $m->income,
                'fixed_costs' => (float) $m->fixed_costs,
                'savings_goal' => (float) $m->savings_goal,
                'intention' => $m->intention,
                'reflection' => $m->reflection,
                'improvement' => $m->improvement,
            ]),
            'entries' => $user->entries()->with('comments')->orderBy('spent_on')->orderBy('id')->get()->map(fn (Entry $e) => [
                'spent_on' => $e->spent_on->toDateString(),
                'category' => $e->category->value,
                'description' => $e->description,
                'amount' => (float) $e->amount,
                'note' => $e->note,
                'created_at' => $e->created_at?->toIso8601String(),
                'comments' => $e->comments->map(fn (Comment $c) => [
                    'body' => $c->body,
                    'created_at' => $c->created_at?->toIso8601String(),
                ]),
            ]),
        ];

        $name = 'kakeibo-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(
            fn () => print(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            $name,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    /** Spreadsheet-friendly list of every expense (opens in Excel with Arabic intact). */
    public function csv(Request $request): StreamedResponse
    {
        $entries = $request->user()->entries()->withCount('comments')->orderBy('spent_on')->orderBy('id')->get();

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel shows Arabic correctly
            fputcsv($out, ['التاريخ', 'القسم', 'الوصف', 'المبلغ', 'ملاحظة', 'عدد التعليقات']);
            foreach ($entries as $e) {
                fputcsv($out, [$e->spent_on->toDateString(), $e->category->label(), $e->description, (float) $e->amount, $e->note, $e->comments_count]);
            }
            fclose($out);
        }, 'kakeibo-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Replace everything in the notebook with the contents of a backup file. */
    public function restore(Request $request): RedirectResponse
    {
        $request->validate(['backup' => ['required', 'file', 'max:20480']]);

        $data = json_decode((string) file_get_contents($request->file('backup')->getRealPath()), true);
        if (! is_array($data) || ($data['format'] ?? null) !== self::FORMAT) {
            return back()->withErrors(['backup' => 'هذا ليس ملف نسخة احتياطية من دفتر كاكيبو.']);
        }

        $validator = Validator::make($data, [
            'settings.name' => ['nullable', 'string', 'max:80'],
            'settings.currency' => ['nullable', 'string', 'max:10'],
            'months' => ['array'],
            'months.*.year' => ['required', 'integer', 'between:2000,2100'],
            'months.*.month' => ['required', 'integer', 'between:1,12'],
            'months.*.income' => ['required', 'numeric', 'min:0'],
            'months.*.fixed_costs' => ['required', 'numeric', 'min:0'],
            'months.*.savings_goal' => ['required', 'numeric', 'min:0'],
            'months.*.intention' => ['nullable', 'string'],
            'months.*.reflection' => ['nullable', 'string'],
            'months.*.improvement' => ['nullable', 'string'],
            'entries' => ['array'],
            'entries.*.spent_on' => ['required', 'date'],
            'entries.*.category' => ['required', Rule::enum(Category::class)],
            'entries.*.description' => ['required', 'string', 'max:160'],
            'entries.*.amount' => ['required', 'numeric', 'gt:0'],
            'entries.*.note' => ['nullable', 'string'],
            'entries.*.created_at' => ['nullable', 'date'],
            'entries.*.comments' => ['array'],
            'entries.*.comments.*.body' => ['required', 'string'],
            'entries.*.comments.*.created_at' => ['nullable', 'date'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors(['backup' => 'ملف النسخة الاحتياطية تالف أو ناقص: '.$validator->errors()->first()]);
        }

        $user = $request->user();

        DB::transaction(function () use ($user, $data) {
            $user->entries()->delete();          // comments go with them (cascade)
            $user->months()->delete();

            if (! empty($data['settings'])) {
                $user->update(array_filter([
                    'name' => $data['settings']['name'] ?? null,
                    'currency' => $data['settings']['currency'] ?? null,
                ]));
            }

            foreach ($data['months'] ?? [] as $m) {
                $user->months()->create($m);
            }

            foreach ($data['entries'] ?? [] as $e) {
                $entry = $user->entries()->make(collect($e)->only(['spent_on', 'category', 'description', 'amount', 'note'])->all());
                if (! empty($e['created_at'])) {
                    $entry->created_at = $e['created_at'];
                }
                $entry->save();

                foreach ($e['comments'] ?? [] as $c) {
                    $comment = new Comment(['body' => $c['body']]);
                    $comment->user()->associate($user);
                    if (! empty($c['created_at'])) {
                        $comment->created_at = $c['created_at'];
                    }
                    $entry->comments()->save($comment);
                }
            }
        });

        return redirect()->route('settings')->with('status', sprintf(
            'استُعيد الدفتر: %d شهر، %d مصروف.', count($data['months'] ?? []), count($data['entries'] ?? [])
        ));
    }
}
