<?php

namespace App\Models;

use App\Enums\BlockType;
use App\Support\BlockText;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * One block of booklet content (paragraph, heading, image, list, math, quote).
 *
 * `uuid` is the stable ID exposed to clients and used by passage anchors;
 * `plain_text` is derived from `data` and is the string anchor offsets index into.
 */
class ContentBlock extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'topic_id', 'type', 'data', 'sort_order'];

    protected function casts(): array
    {
        return [
            'type' => BlockType::class,
            'data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ContentBlock $block) {
            $block->uuid ??= (string) Str::uuid();
        });

        static::saving(function (ContentBlock $block) {
            $block->plain_text = BlockText::plain($block->type, $block->data ?? []);
        });
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function anchors(): HasMany
    {
        return $this->hasMany(PassageAnchor::class);
    }

    /** Short label used in admin selects. */
    public function excerpt(int $length = 80): string
    {
        $text = $this->plain_text ?: ($this->type?->getLabel() ?? '');

        return Str::limit($text, $length);
    }
}
