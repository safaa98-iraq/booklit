<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TopicProgress extends Model
{
    protected $table = 'topic_progress';

    /** A topic counts as completed once the student has read at least this far. */
    public const COMPLETE_AT = 90;

    protected $fillable = ['user_id', 'topic_id', 'booklet_id', 'scroll_percent', 'reading_seconds', 'completed_at', 'last_read_at'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'last_read_at' => 'datetime',
            'scroll_percent' => 'integer',
            'reading_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function booklet(): BelongsTo
    {
        return $this->belongsTo(Booklet::class);
    }
}
