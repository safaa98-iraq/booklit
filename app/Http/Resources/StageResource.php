<?php

namespace App\Http\Resources;

use App\Models\Stage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Stage */
class StageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_ministerial' => $this->is_ministerial,
            'subjects' => SubjectResource::collection($this->whenLoaded('subjects')),
        ];
    }
}
