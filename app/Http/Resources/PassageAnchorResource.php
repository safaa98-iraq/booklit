<?php

namespace App\Http\Resources;

use App\Models\PassageAnchor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PassageAnchor */
class PassageAnchorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'block_id' => $this->block?->uuid,
            'topic_id' => $this->block?->topic_id,
            'booklet_id' => $this->block?->topic?->booklet_id,
            'start' => $this->start_offset,
            'end' => $this->end_offset,
            'text' => $this->quoted_text,
            'is_stale' => $this->isStale(),
        ];
    }
}
