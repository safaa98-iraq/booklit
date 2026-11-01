<?php

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\QuestionLinkType;
use App\Enums\QuestionSource;
use App\Models\Question;
use App\Models\Stage;
use App\Models\User;
use App\Services\BookletContentService;
use Illuminate\Database\Seeder;

/** Sample stages, subjects, a booklet with topics/blocks, questions, an editor and a student. */
class DemoContentSeeder extends Seeder
{
    public function run(BookletContentService $content): void
    {
        $this->call(DatabaseSeeder::class);

        $sixth = Stage::create(['name' => 'السادس الإعدادي', 'is_ministerial' => true, 'sort_order' => 1]);
        $fourth = Stage::create(['name' => 'الرابع الإعدادي', 'is_ministerial' => false, 'sort_order' => 2]);

        $biology = $sixth->subjects()->create(['name' => 'الأحياء', 'color' => '#16a34a', 'sort_order' => 1]);
        $sixth->subjects()->create(['name' => 'الكيمياء', 'color' => '#2563eb', 'sort_order' => 2]);
        $sixth->subjects()->create(['name' => 'الفيزياء', 'color' => '#9333ea', 'sort_order' => 3]);
        $fourthBio = $fourth->subjects()->create(['name' => 'الأحياء', 'color' => '#0d9488', 'sort_order' => 1]);
        $fourth->subjects()->create(['name' => 'الرياضيات', 'color' => '#ea580c', 'sort_order' => 2]);

        $editor = User::firstOrCreate(['email' => 'editor@example.com'], [
            'name' => 'محرر الأحياء', 'password' => 'password', 'role' => 'editor',
        ]);
        $editor->assignedSubjects()->syncWithoutDetaching([$biology->id, $fourthBio->id]);

        User::firstOrCreate(['phone' => '07700000000'], ['name' => 'طالب تجريبي', 'password' => 'password', 'role' => 'student']);

        foreach ([$biology, $fourthBio] as $subject) {
            $booklet = $subject->booklets()->create([
                'title' => 'ملزمة الخلية — الفصل الأول',
                'description' => 'ملزمة شاملة لفصل الخلية مع أسئلة وزارية وأسئلة الأستاذ.',
                'quiz_question_count' => 5,
                'is_published' => true,
                'created_by' => $editor->id,
            ]);

            $cell = $content->createTopic($booklet, ['title' => 'تركيب الخلية', 'blocks' => [
                ['type' => 'heading', 'data' => ['text' => 'الخلية: الوحدة الأساسية للحياة', 'level' => 2]],
                ['type' => 'paragraph', 'data' => ['text' => 'الخلية هي الوحدة الأساسية للتركيب والوظيفة في جميع الكائنات الحية، وتتكون من الغشاء البلازمي والسايتوبلازم والنواة.']],
                ['type' => 'list', 'data' => ['style' => 'bullet', 'items' => ['الغشاء البلازمي: ينظم دخول وخروج المواد.', 'النواة: تتحكم بفعاليات الخلية.', 'الميتوكوندريا: مصنع الطاقة في الخلية.']]],
                ['type' => 'quote', 'data' => ['variant' => 'tip', 'text' => 'تذكّر: الخلايا النباتية تمتلك جداراً خلوياً وبلاستيدات خضراء.']],
            ]]);

            $energy = $content->createTopic($booklet, ['title' => 'الطاقة في الخلية', 'blocks' => [
                ['type' => 'paragraph', 'data' => ['text' => 'تنتج الميتوكوندريا جزيئات ATP خلال عملية التنفس الخلوي الهوائي.']],
                ['type' => 'math', 'data' => ['latex' => 'C_6H_{12}O_6 + 6O_2 \\rightarrow 6CO_2 + 6H_2O + ATP', 'display' => true]],
            ]]);

            $mitoBlock = $cell->blocks()->where('type', 'list')->first();
            $samples = [
                [$cell, QuestionSource::Ministerial, Difficulty::Easy, 'ما هو مصنع الطاقة في الخلية؟', ['الميتوكوندريا', 'النواة', 'الرايبوسوم', 'جهاز كولجي'], 'الميتوكوندريا تنتج ATP.', [$mitoBlock, 'الميتوكوندريا: مصنع الطاقة في الخلية.']],
                [$cell, QuestionSource::Ministerial, Difficulty::Medium, 'أي تركيب يتحكم بفعاليات الخلية؟', ['النواة', 'الغشاء البلازمي', 'الفجوة'], 'النواة مركز السيطرة في الخلية.', [$mitoBlock, 'النواة: تتحكم بفعاليات الخلية.']],
                [$cell, QuestionSource::Teacher, Difficulty::Hard, 'أي مما يلي يوجد في الخلية النباتية فقط؟', ['الجدار الخلوي', 'الميتوكوندريا', 'النواة', 'الغشاء البلازمي', 'السايتوبلازم'], 'الجدار الخلوي يميز الخلية النباتية.', null],
                [$energy, QuestionSource::Teacher, Difficulty::Medium, 'ما الجزيء الناتج عن التنفس الخلوي ويخزن الطاقة؟', ['ATP', 'DNA'], 'ATP هو عملة الطاقة في الخلية.', [$energy->blocks()->first(), 'ATP']],
                [$energy, QuestionSource::Ministerial, Difficulty::Hard, 'أين يحدث التنفس الخلوي الهوائي؟', ['الميتوكوندريا', 'البلاستيدات الخضراء', 'النواة'], null, null],
            ];

            foreach ($samples as [$topic, $source, $difficulty, $text, $options, $explanation, $anchor]) {
                $question = Question::create([
                    'subject_id' => $subject->id,
                    'link_type' => QuestionLinkType::Topic,
                    'topic_id' => $topic->id,
                    'source' => $source,
                    'source_reference' => $source === QuestionSource::Ministerial ? 'وزاري 2024 الدور الأول' : null,
                    'difficulty' => $difficulty,
                    'text' => $text,
                    'explanation' => $explanation,
                    'created_by' => $editor->id,
                ]);

                foreach ($options as $i => $option) {
                    $question->options()->create(['text' => $option, 'is_correct' => $i === 0, 'sort_order' => $i]);
                }

                if ($anchor) {
                    [$block, $phrase] = $anchor;
                    $start = mb_strpos($block->plain_text, $phrase);
                    $question->anchors()->create([
                        'content_block_id' => $block->id,
                        'start_offset' => $start,
                        'end_offset' => $start + mb_strlen($phrase),
                    ]);
                }
            }
        }

        // A standalone question-bank question (no booklet).
        $bank = Question::create([
            'subject_id' => $biology->id, 'link_type' => QuestionLinkType::None,
            'source' => QuestionSource::Ministerial, 'difficulty' => Difficulty::Medium,
            'text' => 'العضية المسؤولة عن تصنيع البروتين هي:', 'explanation' => 'الرايبوسومات تصنع البروتين.',
        ]);
        foreach (['الرايبوسوم', 'الجسم المركزي', 'الليسوسوم'] as $i => $option) {
            $bank->options()->create(['text' => $option, 'is_correct' => $i === 0, 'sort_order' => $i]);
        }
    }
}
