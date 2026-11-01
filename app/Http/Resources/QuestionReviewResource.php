<?php

namespace App\Http\Resources;

use App\Models\Question;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A question with its solution ("أظهر الحل").
 *
 * @mixin Question
 */
class QuestionReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'image_url' => Media::url($this->image),
            'source' => $this->source->value,
            'source_reference' => $this->source_reference,
            'difficulty' => $this->difficulty->value,
            'topic_id' => $this->topic_id,
            'options' => $this->options->shuffle()->map(fn ($o) => (new OptionResource($o))->revealCorrect())->values(),
            'correct_option_id' => $this->options->firstWhere('is_correct', true)?->id,
            'explanation' => $this->explanation,
            'anchors' => PassageAnchorResource::collection($this->whenLoaded('anchors')),
        ];
    }
}
