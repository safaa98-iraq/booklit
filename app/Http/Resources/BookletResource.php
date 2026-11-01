<?php

namespace App\Http\Resources;

use App\Models\Booklet;
use App\Services\SettingsService;
use Illuminate\Http\Request;

/**
 * Full booklet for the reader: topics → blocks, plus what the 4 bottom
 * buttons need (question counts, quiz size, configurable 4th button).
 *
 * @mixin Booklet
 */
class BookletResource extends BookletSummaryResource
{
    /** @param array{ministerial: int, teacher: int, quiz: array<string,int>} $questionStats */
    public function __construct($resource, private readonly array $questionStats = ['ministerial' => 0, 'teacher' => 0, 'quiz' => []])
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'subject' => ['id' => $this->subject->id, 'name' => $this->subject->name],
            'topics' => TopicResource::collection($this->whenLoaded('topics')),
            'actions' => [
                'mcq_ministerial' => ['enabled' => $this->questionStats['ministerial'] > 0, 'questions_count' => $this->questionStats['ministerial']],
                'mcq_teacher' => ['enabled' => $this->questionStats['teacher'] > 0, 'questions_count' => $this->questionStats['teacher']],
                'quiz' => [
                    'enabled' => array_sum($this->questionStats['quiz']) > 0,
                    'question_count' => $this->quiz_question_count,
                    'available_by_difficulty' => $this->questionStats['quiz'],
                ],
                'fourth_button' => app(SettingsService::class)->get('fourth_button'),
            ],
        ];
    }
}
