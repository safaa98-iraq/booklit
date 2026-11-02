<?php

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\QuestionLinkType;
use App\Enums\QuestionSource;
use App\Models\ContentBlock;
use App\Models\Question;
use App\Models\Stage;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\BookletContentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Demo content for a College of Dentistry: 5 stages × 4 subjects, one booklet
 * per subject with 2 topics, and auto-generated MCQs linked to the exact
 * passage they test. Idempotent: does nothing if the stages already exist.
 */
class DentistryContentSeeder extends Seeder
{
    public function run(BookletContentService $content): void
    {
        $data = require __DIR__.'/data/dentistry.php';

        if (Stage::where('name', $data[0]['name'])->exists()) {
            $this->command?->warn('Dentistry content already exists — skipped.');

            return;
        }

        mt_srand(2026); // deterministic distractors

        DB::transaction(function () use ($data, $content) {
            $editor = User::where('email', 'editor@example.com')->first();

            foreach ($data as $stageIndex => $stageData) {
                $stage = Stage::create([
                    'name' => $stageData['name'],
                    'description' => 'كلية طب الأسنان — '.$stageData['name'],
                    'is_ministerial' => $stageData['ministerial'],
                    'sort_order' => $stageIndex + 1,
                ]);

                foreach ($stageData['subjects'] as $subjectIndex => $subjectData) {
                    $subject = $stage->subjects()->create([
                        'name' => $subjectData['name'],
                        'description' => 'ملزمة شاملة لمادة '.$subjectData['name'].' مع أسئلة وزارية وأسئلة الأستاذ.',
                        'color' => $subjectData['color'],
                        'is_ministerial' => $subjectData['ministerial'] ?? false,
                        'sort_order' => $subjectIndex + 1,
                    ]);

                    $editor?->assignedSubjects()->syncWithoutDetaching([$subject->id]);

                    $this->seedSubject($subject, $subjectData, $content, $editor);
                }
            }
        });
    }

    private function seedSubject(Subject $subject, array $subjectData, BookletContentService $content, ?User $editor): void
    {
        $booklet = $subject->booklets()->create([
            'title' => 'ملزمة '.$subject->name,
            'description' => 'ملخص مركز لأهم مفاهيم مادة '.$subject->name.' مع أسئلة محلولة.',
            'quiz_question_count' => 10,
            'is_published' => true,
            'created_by' => $editor?->id,
        ]);

        // All definitions/terms of the subject, used as distractors.
        $allFacts = collect($subjectData['topics'])->flatMap(fn ($t) => $t['facts']);

        foreach ($subjectData['topics'] as $topicData) {
            $lines = collect($topicData['facts'])->map(fn ($def, $term) => "{$term}: {$def}")->values()->all();

            $blocks = [
                ['type' => 'paragraph', 'data' => ['text' => $topicData['intro']]],
                ['type' => 'heading', 'data' => ['text' => 'أهم المصطلحات', 'level' => 3]],
                ['type' => 'list', 'data' => ['style' => 'bullet', 'items' => $lines]],
            ];
            if (isset($topicData['math'])) {
                $blocks[] = ['type' => 'math', 'data' => ['latex' => $topicData['math'], 'display' => true]];
            }
            $blocks[] = ['type' => 'quote', 'data' => [
                'variant' => 'tip',
                'text' => 'نصيحة للامتحان: احفظ المصطلح الإنكليزي مع تعريفه، فالأسئلة الوزارية تأتي غالباً بصيغة التعريف.',
            ]];

            $topic = $content->createTopic($booklet, ['title' => $topicData['title'], 'blocks' => $blocks]);
            $listBlock = $topic->blocks()->where('type', 'list')->firstOrFail();

            $i = 0;
            foreach ($topicData['facts'] as $term => $definition) {
                $line = "{$term}: {$definition}";
                $otherDefs = $allFacts->reject(fn ($d, $t) => $t === $term);

                // Form A: term → definition.
                $this->question($subject, $topic, $listBlock, $line, [
                    'source' => $i % 2 === 0 ? QuestionSource::Ministerial : QuestionSource::Teacher,
                    'difficulty' => $i % 2 === 0 ? Difficulty::Easy : Difficulty::Medium,
                    'text' => "ما المقصود بـ {$term}؟",
                    'correct' => $definition,
                    'wrong' => Arr::random($otherDefs->values()->all(), 3),
                    'explanation' => "{$term} هو: {$definition}.",
                ]);

                // Form B: definition → term.
                $this->question($subject, $topic, $listBlock, $line, [
                    'source' => $i % 2 === 0 ? QuestionSource::Teacher : QuestionSource::Ministerial,
                    'difficulty' => $i % 2 === 0 ? Difficulty::Medium : Difficulty::Hard,
                    'text' => "أي مصطلح ينطبق عليه الوصف التالي: «{$definition}»؟",
                    'correct' => $term,
                    'wrong' => Arr::random($otherDefs->keys()->all(), 3),
                    'explanation' => "الوصف المذكور هو تعريف {$term}.",
                ]);

                // Standalone bank: one true/false question per topic.
                if ($i === 0) {
                    $q = Question::create([
                        'subject_id' => $subject->id,
                        'link_type' => QuestionLinkType::None,
                        'source' => QuestionSource::Teacher,
                        'difficulty' => Difficulty::Easy,
                        'text' => "صح أم خطأ: {$line}",
                        'explanation' => 'العبارة صحيحة كما وردت في الملزمة.',
                        'created_by' => $editor?->id,
                    ]);
                    $q->options()->createMany([
                        ['text' => 'صح', 'is_correct' => true, 'sort_order' => 0],
                        ['text' => 'خطأ', 'is_correct' => false, 'sort_order' => 1],
                    ]);
                }

                $i++;
            }
        }
    }

    private function question(Subject $subject, Topic $topic, ContentBlock $block, string $line, array $q): void
    {
        $question = Question::create([
            'subject_id' => $subject->id,
            'link_type' => QuestionLinkType::Topic,
            'topic_id' => $topic->id,
            'source' => $q['source'],
            'source_reference' => $q['source'] === QuestionSource::Ministerial ? 'وزاري '.Arr::random([2021, 2022, 2023, 2024, 2025]).' — '.Arr::random(['الدور الأول', 'الدور الثاني']) : null,
            'difficulty' => $q['difficulty'],
            'text' => $q['text'],
            'explanation' => $q['explanation'],
        ]);

        $options = array_merge([['text' => $q['correct'], 'is_correct' => true]], array_map(fn ($w) => ['text' => $w, 'is_correct' => false], $q['wrong']));
        foreach ($options as $sort => $option) {
            $question->options()->create($option + ['sort_order' => $sort]);
        }

        $start = mb_strpos($block->plain_text, $line);
        $question->anchors()->create([
            'content_block_id' => $block->id,
            'start_offset' => $start,
            'end_offset' => $start + mb_strlen($line),
        ]);
    }
}
