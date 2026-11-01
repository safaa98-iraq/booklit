<?php

namespace Database\Factories;

use App\Models\Booklet;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Topic> */
class TopicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booklet_id' => Booklet::factory(),
            'title' => 'الموضوع '.fake()->numberBetween(1, 50),
            'sort_order' => 0,
        ];
    }
}
