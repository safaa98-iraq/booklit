<?php

namespace App\Http\Resources;

use App\Models\Booklet;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booklet */
class BookletSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image_url' => Media::url($this->cover_image),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
