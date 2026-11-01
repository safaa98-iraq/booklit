<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stage' => $this->stage_id ? ['id' => $this->stage_id, 'name' => $this->stage?->name] : null,
            'subject' => $this->subject_id ? ['id' => $this->subject_id, 'name' => $this->subject?->name] : null,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => $this->isCurrentlyActive(),
        ];
    }
}
