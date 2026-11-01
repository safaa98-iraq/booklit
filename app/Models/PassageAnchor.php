<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Points a question at an exact passage: [start_offset, end_offset) in the
 * block's plain text, measured in Unicode code points.
 */
class PassageAnchor extends Model
{
    use HasFactory;

    protected $fillable = ['question_id', 'content_block_id', 'start_offset', 'end_offset', 'quoted_text'];

    protected function casts(): array
    {
        return [
            'start_offset' => 'integer',
            'end_offset' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PassageAnchor $anchor) {
            if ($anchor->isDirty(['content_block_id', 'start_offset', 'end_offset']) || $anchor->quoted_text === null) {
                $anchor->quoted_text = $anchor->currentText();
            }
        });
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(ContentBlock::class, 'content_block_id');
    }

    /** The text the offsets currently select in the (possibly edited) block. */
    public function currentText(): ?string
    {
        $text = $this->block?->plain_text;

        if ($text === null || $this->end_offset <= $this->start_offset) {
            return null;
        }

        return mb_substr($text, $this->start_offset, $this->end_offset - $this->start_offset);
    }

    /** True when the block was edited after anchoring and the offsets no longer select the original text. */
    public function isStale(): bool
    {
        return $this->quoted_text !== null && $this->currentText() !== $this->quoted_text;
    }
}
