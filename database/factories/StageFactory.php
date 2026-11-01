<?php

namespace Database\Factories;

use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Stage> */
class StageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'المرحلة '.fake()->unique()->numberBetween(1, 9999),
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
