<?php

namespace Database\Factories;

use App\Models\Stage;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subject> */
class SubjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'stage_id' => Stage::factory(),
            'name' => fake()->randomElement(['الأحياء', 'الكيمياء', 'الفيزياء', 'الرياضيات', 'اللغة العربية']).' '.fake()->unique()->numberBetween(1, 9999),
            'is_ministerial' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function ministerial(): static
    {
        return $this->state(fn () => ['is_ministerial' => true]);
    }
}
