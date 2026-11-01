<?php

namespace App\Http\Resources;

use App\Models\QuestionOption;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin QuestionOption */
class OptionResource extends JsonResource
{
    private bool $revealCorrect = false;

    public function revealCorrect(bool $reveal = true): static
    {
        $this->revealCorrect = $reveal;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'image_url' => Media::url($this->image),
            'is_correct' => $this->when($this->revealCorrect, fn () => $this->is_correct),
        ];
    }
}
