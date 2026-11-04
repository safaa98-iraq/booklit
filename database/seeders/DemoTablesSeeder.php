<?php

namespace Database\Seeders;

use App\Models\Subject;
use App\Services\BookletContentService;
use Illuminate\Database\Seeder;

/** Adds a "quick review" table to the first topic of every demo subject (idempotent). */
class DemoTablesSeeder extends Seeder
{
    public function run(BookletContentService $content): void
    {
        foreach (Subject::with('booklets.topics.blocks')->get() as $subject) {
            $topic = $subject->booklets->first()?->topics->first();
            if (! $topic || $topic->blocks->contains(fn ($b) => $b->type->value === 'table')) {
                continue;
            }

            $list = $topic->blocks->first(fn ($b) => $b->type->value === 'list');
            if (! $list) {
                continue;
            }

            $rows = [['المصطلح', 'التعريف']];
            foreach ($list->data['items'] ?? [] as $item) {
                [$term, $definition] = array_pad(explode(': ', (string) $item, 2), 2, '');
                $rows[] = [$term, $definition];
            }

            $state = $content->editorState($topic);
            array_splice($state, count($state) - 1, 0, [[
                'type' => 'heading', 'data' => ['text' => 'جدول مراجعة سريعة', 'level' => 3],
            ], [
                'type' => 'table', 'data' => ['header' => true, 'rows' => $rows],
            ]]);
            $content->syncBlocks($topic, $state);
        }
    }
}
