<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            // Text rendered as a dynamic watermark over booklet content (web).
            'watermark' => trim("{$this->name} • {$this->phone}"),
            'subscriptions' => SubscriptionResource::collection($this->whenLoaded('subscriptions')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
