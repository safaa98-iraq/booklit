<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\QuestionLinkType;
use App\Enums\QuestionSource;
use App\Models\Booklet;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Question> */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'link_type' => QuestionLinkType::None,
            'source' => QuestionSource::Teacher,
            'difficulty' => Difficulty::Medium,
            'text' => 'ما هي الوحدة الأساسية للحياة؟ '.fake()->unique()->numberBetween(1, 99999),
            'explanation' => 'الخلية هي الوحدة الأساسية للتركيب والوظيفة.',
            'is_active' => true,
        ];
    }

    /** Attach $count options (default 4); the first one is correct. */
    public function withOptions(int $count = 4): static
    {
        return $this->afterCreating(function (Question $question) use ($count) {
            foreach (range(1, $count) as $i) {
                $question->options()->create([
                    'text' => "الخيار $i",
                    'is_correct' => $i === 1,
                    'sort_order' => $i,
                ]);
            }
        });
    }

    public function forBooklet(Booklet $booklet): static
    {
        return $this->state(fn () => [
            'subject_id' => $booklet->subject_id,
            'booklet_id' => $booklet->getKey(),
            'link_type' => QuestionLinkType::Booklet,
        ]);
    }

    public function forTopic(Topic $topic): static
    {
        return $this->state(fn () => [
            'subject_id' => $topic->booklet->subject_id,
            'booklet_id' => $topic->booklet_id,
            'topic_id' => $topic->getKey(),
            'link_type' => QuestionLinkType::Topic,
        ]);
    }

    public function ministerial(): static
    {
        return $this->state(fn () => ['source' => QuestionSource::Ministerial]);
    }

    public function teacher(): static
    {
        return $this->state(fn () => ['source' => QuestionSource::Teacher]);
    }

    public function difficulty(Difficulty $difficulty): static
    {
        return $this->state(fn () => ['difficulty' => $difficulty]);
    }
}
