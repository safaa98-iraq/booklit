<?php

namespace App\Http\Resources;

use App\Models\Subject;
use App\Services\SubscriptionService;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subject */
class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $access = app(SubscriptionService::class);
        $locked = $request->user() ? $access->isLocked($request->user(), $this->resource) : $this->requiresSubscription();

        return [
            'id' => $this->id,
            'stage_id' => $this->stage_id,
            'name' => $this->name,
            'description' => $this->description,
            'cover_image_url' => Media::url($this->cover_image),
            'color' => $this->color,
            'is_ministerial' => $this->requiresSubscription(),
            'is_locked' => $locked,
            'booklets' => $this->when(! $locked && $this->relationLoaded('booklets'), fn () => BookletSummaryResource::collection($this->booklets)),
        ];
    }
}
