<?php

namespace App\Services;

use App\Enums\BlockType;
use App\Models\Booklet;
use App\Models\ContentBlock;
use App\Models\Topic;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Persists the block editor state of a topic while preserving block UUIDs,
 * so passage anchors survive re-ordering and edits.
 *
 * Editor state format (Filament Builder / TipTap-like):
 *   [ ['type' => 'paragraph', 'data' => ['block_uuid' => '…', 'text' => '…']], … ]
 */
class BookletContentService
{
    /** Allowed data keys per block type. */
    private const SCHEMA = [
        'paragraph' => ['text'],
        'heading' => ['text', 'level'],
        'image' => ['path', 'caption', 'alt'],
        'list' => ['style', 'items'],
        'math' => ['latex', 'display'],
        'quote' => ['text', 'variant'],
    ];

    /** Convert stored blocks into editor state. */
    public function editorState(Topic $topic): array
    {
        return $topic->blocks()->get()
            ->map(fn (ContentBlock $block) => [
                'type' => $block->type->value,
                'data' => ['block_uuid' => $block->uuid] + ($block->data ?? []),
            ])
            ->values()
            ->all();
    }

    /**
     * Upsert blocks from editor state; blocks missing from the state are deleted
     * (their passage anchors are removed by the FK cascade).
     */
    public function syncBlocks(Topic $topic, array $state): void
    {
        DB::transaction(function () use ($topic, $state) {
            $bookletTopicIds = Topic::where('booklet_id', $topic->booklet_id)->pluck('id');
            $keep = [];
            $seen = [];

            foreach (array_values($state) as $position => $item) {
                $type = BlockType::tryFrom((string) ($item['type'] ?? ''));
                if (! $type) {
                    continue;
                }

                $data = $item['data'] ?? [];
                $uuid = $data['block_uuid'] ?? null;

                // A cloned block arrives with its source's UUID: treat the copy as new.
                if ($uuid && isset($seen[$uuid])) {
                    $uuid = null;
                }
                if ($uuid) {
                    $seen[$uuid] = true;
                }

                $clean = $this->normalize($type, $data);

                $block = $uuid
                    ? ContentBlock::where('uuid', $uuid)->whereIn('topic_id', $bookletTopicIds)->first()
                    : null;

                if ($block) {
                    // Also handles a block moved between topics of the same booklet.
                    $block->fill([
                        'topic_id' => $topic->getKey(),
                        'type' => $type,
                        'data' => $clean,
                        'sort_order' => $position,
                    ])->save();
                } else {
                    $block = $topic->blocks()->create([
                        'uuid' => Str::isUuid((string) $uuid) && ! ContentBlock::where('uuid', $uuid)->exists()
                            ? $uuid
                            : (string) Str::uuid(),
                        'type' => $type,
                        'data' => $clean,
                        'sort_order' => $position,
                    ]);
                }

                $keep[] = $block->getKey();
            }

            $topic->blocks()->whereNotIn('id', $keep)->delete();
        });
    }

    /** Create a topic (title + blocks) at the end of the booklet. */
    public function createTopic(Booklet $booklet, array $data): Topic
    {
        return DB::transaction(function () use ($booklet, $data) {
            $topic = $booklet->topics()->create([
                'title' => $data['title'],
                'sort_order' => $data['sort_order'] ?? ((int) $booklet->topics()->max('sort_order') + 1),
            ]);

            $this->syncBlocks($topic, $data['blocks'] ?? []);

            return $topic;
        });
    }

    public function updateTopic(Topic $topic, array $data): Topic
    {
        return DB::transaction(function () use ($topic, $data) {
            $topic->update(Arr::only($data, ['title', 'sort_order']));

            if (array_key_exists('blocks', $data)) {
                $this->syncBlocks($topic, $data['blocks'] ?? []);
            }

            return $topic;
        });
    }

    private function normalize(BlockType $type, array $data): array
    {
        $clean = Arr::only($data, self::SCHEMA[$type->value]);

        return match ($type) {
            BlockType::Heading => ['text' => (string) ($clean['text'] ?? ''), 'level' => (int) ($clean['level'] ?? 2)],
            BlockType::List => [
                'style' => in_array($clean['style'] ?? null, ['ordered', 'bullet'], true) ? $clean['style'] : 'bullet',
                'items' => array_values(array_filter(
                    array_map(fn ($i) => is_array($i) ? (string) ($i['text'] ?? '') : (string) $i, $clean['items'] ?? []),
                    fn ($i) => $i !== ''
                )),
            ],
            BlockType::Math => ['latex' => (string) ($clean['latex'] ?? ''), 'display' => (bool) ($clean['display'] ?? true)],
            BlockType::Quote => ['text' => (string) ($clean['text'] ?? ''), 'variant' => $clean['variant'] ?? 'note'],
            BlockType::Image => [
                'path' => is_array($clean['path'] ?? null) ? (Arr::first($clean['path']) ?? null) : ($clean['path'] ?? null),
                'caption' => $clean['caption'] ?? null,
                'alt' => $clean['alt'] ?? null,
            ],
            default => ['text' => (string) ($clean['text'] ?? '')],
        };
    }
}
