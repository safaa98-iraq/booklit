<?php

namespace Database\Factories;

use App\Models\Booklet;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Booklet> */
class BookletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'title' => 'ملزمة '.fake()->unique()->numberBetween(1, 9999),
            'description' => 'وصف الملزمة',
            'quiz_question_count' => 10,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
