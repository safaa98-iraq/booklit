<?php

namespace App\Http\Resources;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An attempt. While in progress, correct answers are never exposed; once
 * submitted, each question carries the correct option, explanation and
 * passage anchors so the app can highlight the source text in the booklet.
 *
 * @mixin Attempt
 */
class AttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $submitted = $this->isSubmitted();

        return [
            'id' => $this->id,
            'mode' => $this->mode->value,
            'difficulty' => $this->difficulty?->value,
            'status' => $this->status->value,
            'subject_id' => $this->subject_id,
            'booklet_id' => $this->booklet_id,
            'total_questions' => $this->total_questions,
            'answered_count' => $this->whenLoaded('answers', fn () => $this->answers->whereNotNull('selected_option_id')->count()),
            'correct_count' => $this->when($submitted, $this->correct_count),
            'score' => $this->when($submitted, $this->score),
            'started_at' => $this->started_at?->toIso8601String(),
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'questions' => $this->whenLoaded('answers', fn () => $this->answers->map(fn (AttemptAnswer $a) => $this->question($a, $submitted))->values()),
        ];
    }

    private function question(AttemptAnswer $answer, bool $submitted): array
    {
        $question = $answer->question;
        $options = $question->options->keyBy('id');

        // Present options in the stored random order; append any added later.
        $ordered = collect($answer->option_order)
            ->map(fn ($id) => $options->get($id))
            ->filter()
            ->concat($options->except($answer->option_order ?? [])->values());

        $row = [
            'question_id' => $question->id,
            'position' => $answer->position,
            'text' => $question->text,
            'image_url' => Media::url($question->image),
            'difficulty' => $question->difficulty->value,
            'source' => $question->source->value,
            'options' => $ordered->map(fn ($o) => (new OptionResource($o))->revealCorrect($submitted))->values(),
            'selected_option_id' => $answer->selected_option_id,
        ];

        if ($submitted) {
            $row += [
                'is_correct' => (bool) $answer->is_correct,
                'correct_option_id' => $question->options->firstWhere('is_correct', true)?->id,
                'explanation' => $question->explanation,
                'topic_id' => $question->topic_id,
                'anchors' => PassageAnchorResource::collection($question->relationLoaded('anchors') ? $question->anchors : collect()),
            ];
        }

        return $row;
    }
}
