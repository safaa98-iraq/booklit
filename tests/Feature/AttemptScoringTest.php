<?php

namespace Tests\Feature;

use App\Enums\Difficulty;
use App\Models\Booklet;
use App\Models\ContentBlock;
use App\Models\Question;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithStudentApi;
use Tests\TestCase;

class AttemptScoringTest extends TestCase
{
    use InteractsWithStudentApi, RefreshDatabase;

    private Booklet $booklet;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->booklet = Booklet::factory()->create(['quiz_question_count' => 3]);
        $this->topic = Topic::factory()->for($this->booklet)->create();
    }

    private function correctOptionId(int $questionId): int
    {
        return Question::findOrFail($questionId)->correctOption->id;
    }

    private function wrongOptionId(int $questionId): int
    {
        return Question::findOrFail($questionId)->options()->where('is_correct', false)->firstOrFail()->id;
    }

    public function test_attempt_hides_answers_until_submitted_and_scores_correctly(): void
    {
        Question::factory()->count(4)->forTopic($this->topic)->ministerial()->withOptions(4)->create();
        Question::factory()->count(2)->forBooklet($this->booklet)->teacher()->withOptions(3)->create(); // other source
        [, $headers] = $this->actingAsStudentOnDevice();

        $start = $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'ministerial'], $headers)
            ->assertCreated()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.total_questions', 4)
            ->assertJsonMissingPath('data.score')
            ->assertJsonMissingPath('data.questions.0.correct_option_id')
            ->assertJsonMissingPath('data.questions.0.options.0.is_correct')
            ->assertJsonMissingPath('data.questions.0.explanation');

        $attemptId = $start->json('data.id');
        $questions = collect($start->json('data.questions'));
        $this->assertSame(4, $questions->pluck('question_id')->unique()->count());

        // 3 correct, 1 wrong.
        $answers = $questions->values()->map(fn ($q, $i) => [
            'question_id' => $q['question_id'],
            'option_id' => $i < 3 ? $this->correctOptionId($q['question_id']) : $this->wrongOptionId($q['question_id']),
        ])->all();

        $this->postJson("/api/v1/attempts/{$attemptId}/submit", ['answers' => $answers], $headers)
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.correct_count', 3)
            ->assertJsonPath('data.score', 75)
            ->assertJsonStructure(['data' => ['questions' => [['is_correct', 'correct_option_id', 'explanation', 'anchors']]]]);
    }

    public function test_student_can_change_answers_before_submitting_and_last_answer_counts(): void
    {
        $q = Question::factory()->forBooklet($this->booklet)->teacher()->withOptions(4)->create();
        [, $headers] = $this->actingAsStudentOnDevice();

        $attemptId = $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'teacher'], $headers)
            ->assertCreated()->json('data.id');

        $this->putJson("/api/v1/attempts/{$attemptId}/answers", ['answers' => [['question_id' => $q->id, 'option_id' => $this->wrongOptionId($q->id)]]], $headers)
            ->assertOk()->assertJsonPath('data.answered_count', 1);

        $this->putJson("/api/v1/attempts/{$attemptId}/answers", ['answers' => [['question_id' => $q->id, 'option_id' => $this->correctOptionId($q->id)]]], $headers)
            ->assertOk();

        $this->postJson("/api/v1/attempts/{$attemptId}/submit", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.correct_count', 1)
            ->assertJsonPath('data.score', 100);
    }

    public function test_unanswered_questions_count_as_wrong(): void
    {
        Question::factory()->count(4)->forBooklet($this->booklet)->teacher()->withOptions(2)->create();
        [, $headers] = $this->actingAsStudentOnDevice();

        $start = $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'teacher'], $headers)->assertCreated();
        $first = $start->json('data.questions.0.question_id');

        $this->postJson("/api/v1/attempts/{$start->json('data.id')}/submit", [
            'answers' => [['question_id' => $first, 'option_id' => $this->correctOptionId($first)]],
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.correct_count', 1)
            ->assertJsonPath('data.score', 25);
    }

    public function test_attempt_cannot_be_submitted_twice_or_changed_after_submission(): void
    {
        $q = Question::factory()->forBooklet($this->booklet)->teacher()->withOptions(4)->create();
        [, $headers] = $this->actingAsStudentOnDevice();
        $attemptId = $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'teacher'], $headers)->json('data.id');

        $this->postJson("/api/v1/attempts/{$attemptId}/submit", [], $headers)->assertOk()->assertJsonPath('data.score', 0);

        $this->postJson("/api/v1/attempts/{$attemptId}/submit", [], $headers)->assertUnprocessable();
        $this->putJson("/api/v1/attempts/{$attemptId}/answers", ['answers' => [['question_id' => $q->id, 'option_id' => $this->correctOptionId($q->id)]]], $headers)
            ->assertUnprocessable();
    }

    public function test_options_must_belong_to_the_question(): void
    {
        $q1 = Question::factory()->forBooklet($this->booklet)->teacher()->withOptions(4)->create();
        $q2 = Question::factory()->forBooklet($this->booklet)->teacher()->withOptions(4)->create();
        $foreign = Question::factory()->withOptions(2)->create();
        [, $headers] = $this->actingAsStudentOnDevice();
        $attemptId = $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'teacher'], $headers)->json('data.id');

        $this->putJson("/api/v1/attempts/{$attemptId}/answers", ['answers' => [['question_id' => $q1->id, 'option_id' => $this->correctOptionId($q2->id)]]], $headers)
            ->assertUnprocessable()->assertJsonValidationErrors('answers.0.option_id');

        $this->putJson("/api/v1/attempts/{$attemptId}/answers", ['answers' => [['question_id' => $foreign->id, 'option_id' => $this->correctOptionId($foreign->id)]]], $headers)
            ->assertUnprocessable()->assertJsonValidationErrors('answers.0.question_id');
    }

    public function test_students_cannot_touch_other_students_attempts(): void
    {
        Question::factory()->forBooklet($this->booklet)->teacher()->withOptions(4)->create();
        [, $ownerHeaders] = $this->actingAsStudentOnDevice();
        $attemptId = $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'teacher'], $ownerHeaders)->json('data.id');

        $this->app['auth']->forgetGuards();
        $intruder = User::factory()->create();
        $token = $this->loginStudent($intruder, 'intruder-device');
        $headers = $this->studentHeaders($token, 'intruder-device');

        $this->getJson("/api/v1/attempts/{$attemptId}", $headers)->assertForbidden();
        $this->postJson("/api/v1/attempts/{$attemptId}/submit", [], $headers)->assertForbidden();
    }

    public function test_quiz_mode_requires_difficulty_and_uses_editor_defined_size(): void
    {
        Question::factory()->count(5)->forBooklet($this->booklet)->ministerial()->difficulty(Difficulty::Hard)->withOptions(4)->create();
        Question::factory()->count(5)->forTopic($this->topic)->teacher()->difficulty(Difficulty::Hard)->withOptions(4)->create();
        Question::factory()->count(5)->forBooklet($this->booklet)->teacher()->difficulty(Difficulty::Easy)->withOptions(4)->create();
        [, $headers] = $this->actingAsStudentOnDevice();

        $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'quiz'], $headers)
            ->assertUnprocessable()->assertJsonValidationErrors('difficulty');

        $response = $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'quiz', 'difficulty' => 'hard'], $headers)
            ->assertCreated()
            ->assertJsonPath('data.total_questions', 3)
            ->assertJsonPath('data.difficulty', 'hard');

        foreach ($response->json('data.questions') as $question) {
            $this->assertSame('hard', $question['difficulty']);
        }
    }

    public function test_options_are_presented_in_a_stable_shuffled_order(): void
    {
        $q = Question::factory()->forBooklet($this->booklet)->teacher()->withOptions(6)->create();
        [, $headers] = $this->actingAsStudentOnDevice();

        $start = $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'teacher'], $headers)->assertCreated();
        $order = collect($start->json('data.questions.0.options'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing($q->options->pluck('id')->all(), $order);

        // Re-fetching keeps the same order so answers don't jump around.
        $this->getJson("/api/v1/attempts/{$start->json('data.id')}", $headers)
            ->assertOk()
            ->assertJsonPath('data.questions.0.options', fn ($options) => collect($options)->pluck('id')->all() === $order);
    }

    public function test_submission_returns_passage_anchor_to_highlight(): void
    {
        $block = ContentBlock::factory()->for($this->topic)->create([
            'data' => ['text' => 'الخلية هي الوحدة الأساسية للحياة.'],
        ]);
        $q = Question::factory()->forTopic($this->topic)->teacher()->withOptions(4)->create();
        $q->anchors()->create(['content_block_id' => $block->id, 'start_offset' => 0, 'end_offset' => 6]);
        [, $headers] = $this->actingAsStudentOnDevice();

        $attemptId = $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'teacher'], $headers)->json('data.id');

        $this->postJson("/api/v1/attempts/{$attemptId}/submit", [], $headers)
            ->assertOk()
            ->assertJsonPath('data.questions.0.anchors.0.block_id', $block->uuid)
            ->assertJsonPath('data.questions.0.anchors.0.topic_id', $this->topic->id)
            ->assertJsonPath('data.questions.0.anchors.0.start', 0)
            ->assertJsonPath('data.questions.0.anchors.0.end', 6)
            ->assertJsonPath('data.questions.0.anchors.0.text', 'الخلية')
            ->assertJsonPath('data.questions.0.anchors.0.is_stale', false);
    }

    public function test_starting_without_available_questions_fails_gracefully(): void
    {
        [, $headers] = $this->actingAsStudentOnDevice();

        $this->postJson('/api/v1/attempts', ['booklet_id' => $this->booklet->id, 'mode' => 'ministerial'], $headers)
            ->assertUnprocessable()->assertJsonValidationErrors('mode');
    }

    public function test_review_mode_shows_solutions_without_an_attempt(): void
    {
        Question::factory()->count(2)->forBooklet($this->booklet)->ministerial()->withOptions(4)->create();
        Question::factory()->forBooklet($this->booklet)->teacher()->withOptions(4)->create();
        [, $headers] = $this->actingAsStudentOnDevice();

        $this->getJson("/api/v1/booklets/{$this->booklet->id}/questions/review?source=ministerial", $headers)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['correct_option_id', 'explanation', 'options' => [['is_correct']]]]]);
    }

    public function test_subject_question_bank_attempt(): void
    {
        $subject = $this->booklet->subject;
        Question::factory()->count(3)->for($subject)->teacher()->withOptions(4)->create(); // link_type none
        Question::factory()->forBooklet($this->booklet)->teacher()->withOptions(4)->create(); // not in bank
        [, $headers] = $this->actingAsStudentOnDevice();

        $this->postJson('/api/v1/attempts', ['subject_id' => $subject->id, 'mode' => 'teacher'], $headers)
            ->assertCreated()
            ->assertJsonPath('data.total_questions', 3)
            ->assertJsonPath('data.booklet_id', null);
    }
}
