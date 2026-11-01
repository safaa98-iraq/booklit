<?php

namespace Database\Factories;

use App\Enums\BlockType;
use App\Models\ContentBlock;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContentBlock> */
class ContentBlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'type' => BlockType::Paragraph,
            'data' => ['text' => 'الخلية هي الوحدة الأساسية للتركيب والوظيفة في الكائنات الحية.'],
            'sort_order' => 0,
        ];
    }
}
