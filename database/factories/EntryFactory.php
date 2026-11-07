<?php

namespace Database\Factories;

use App\Enums\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Entry> */
class EntryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'spent_on' => now()->toDateString(),
            'category' => fake()->randomElement(Category::cases()),
            'description' => fake()->words(2, true),
            'amount' => fake()->numberBetween(1, 100) * 1000,
            'note' => null,
        ];
    }
}
