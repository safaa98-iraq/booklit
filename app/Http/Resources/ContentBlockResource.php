<?php

namespace App\Http\Resources;

use App\Enums\BlockType;
use App\Models\ContentBlock;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ContentBlock */
class ContentBlockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];

        if ($this->type === BlockType::Image) {
            $data['url'] = Media::url($data['path'] ?? null);
            unset($data['path']);
        }

        return [
            'id' => $this->uuid,          // stable block ID referenced by passage anchors
            'type' => $this->type->value,
            'data' => $data,
            'plain_text' => $this->plain_text, // offsets of passage anchors index into this
        ];
    }
}
