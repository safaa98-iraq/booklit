<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHighlight extends Model
{
    protected $fillable = ['user_id', 'content_block_id', 'start_offset', 'end_offset'];

    protected function casts(): array
    {
        return ['start_offset' => 'integer', 'end_offset' => 'integer'];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(ContentBlock::class, 'content_block_id');
    }
}
