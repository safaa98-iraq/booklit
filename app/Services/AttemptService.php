<?php

namespace App\Services;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\Difficulty;
use App\Enums\QuestionLinkType;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Booklet;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Starts, saves and scores question-solving attempts.
 *
 * Questions and their options are shuffled once at start and the order is
 * stored, so the student sees a stable order while changing answers.
 */
class AttemptService
{
    /**
     * Start an attempt on a booklet, or on a subject's standalone question bank.
     */
    public function start(User $user, AttemptMode $mode, ?Booklet $booklet = null, ?Subject $subject = null, ?Difficulty $difficulty = null): Attempt
    {
        if (! $booklet && ! $subject) {
            throw new \InvalidArgumentException('A booklet or a subject is required.');
        }

        if ($mode === AttemptMode::Quiz && ! $difficulty) {
            throw ValidationException::withMessages(['difficulty' => 'يرجى اختيار مستوى الصعوبة.']);
        }

        $subject ??= $booklet->subject;

        $query = $this->questionPool($mode, $booklet, $subject, $mode === AttemptMode::Quiz ? $difficulty : null);

        $questions = $mode === AttemptMode::Quiz
            ? $query->inRandomOrder()->limit($booklet?->quiz_question_count ?: config('booklit.default_quiz_size'))->get()
            : $query->get()->shuffle();

        if ($questions->isEmpty()) {
            throw ValidationException::withMessages(['mode' => 'لا توجد أسئلة متاحة لهذا الاختيار حالياً.']);
        }

        return DB::transaction(function () use ($user, $mode, $booklet, $subject, $difficulty, $questions) {
            $attempt = Attempt::create([
                'user_id' => $user->getKey(),
                'subject_id' => $subject->getKey(),
                'booklet_id' => $booklet?->getKey(),
                'mode' => $mode,
                'difficulty' => $mode === AttemptMode::Quiz ? $difficulty : null,
                'status' => AttemptStatus::InProgress,
                'total_questions' => $questions->count(),
                'started_at' => now(),
            ]);

            $now = now();
            AttemptAnswer::insert($questions->values()->map(fn (Question $q, int $i) => [
                'attempt_id' => $attempt->getKey(),
                'question_id' => $q->getKey(),
                'topic_id' => $q->topic_id,
                'position' => $i + 1,
                'option_order' => json_encode($q->options->pluck('id')->shuffle()->values()->all()),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            return $attempt->load(['answers.question.options']);
        });
    }

    /**
     * Save (or change) answers on an in-progress attempt.
     *
     * @param  list<array{question_id: int, option_id: ?int}>  $answers
     */
    public function saveAnswers(Attempt $attempt, array $answers): Attempt
    {
        $this->ensureInProgress($attempt);

        $rows = $attempt->answers()->with('question.options:id,question_id')->get()->keyBy('question_id');

        DB::transaction(function () use ($answers, $rows) {
            foreach ($answers as $i => $answer) {
                $row = $rows->get((int) $answer['question_id']);

                if (! $row) {
                    throw ValidationException::withMessages([
                        "answers.$i.question_id" => 'هذا السؤال لا ينتمي إلى هذه المحاولة.',
                    ]);
                }

                $optionId = $answer['option_id'] ?? null;

                if ($optionId !== null && ! $row->question->options->contains('id', (int) $optionId)) {
                    throw ValidationException::withMessages([
                        "answers.$i.option_id" => 'الخيار المحدد لا ينتمي إلى هذا السؤال.',
                    ]);
                }

                $row->update([
                    'selected_option_id' => $optionId,
                    'answered_at' => $optionId ? now() : null,
                ]);
            }
        });

        return $attempt->refresh();
    }

    /**
     * Finalise and score an attempt. Optionally applies a last batch of answers first.
     */
    public function submit(Attempt $attempt, array $answers = []): Attempt
    {
        return DB::transaction(function () use ($attempt, $answers) {
            // Lock to make double-submission idempotent-safe.
            $attempt = Attempt::whereKey($attempt->getKey())->lockForUpdate()->firstOrFail();

            if ($answers !== []) {
                $this->saveAnswers($attempt, $answers);
            }

            $this->ensureInProgress($attempt);

            $rows = $attempt->answers()->with('question.correctOption:id,question_id')->get();
            $correct = 0;

            foreach ($rows as $row) {
                $isCorrect = $row->selected_option_id !== null
                    && $row->selected_option_id === $row->question->correctOption?->getKey();

                $row->update(['is_correct' => $isCorrect]);
                $correct += (int) $isCorrect;
            }

            $total = $rows->count();

            $attempt->update([
                'status' => AttemptStatus::Submitted,
                'total_questions' => $total,
                'correct_count' => $correct,
                'score' => $total > 0 ? round($correct / $total * 100, 2) : 0,
                'submitted_at' => now(),
            ]);

            return $attempt->refresh();
        });
    }

    /**
     * Questions eligible for an attempt / review.
     */
    public function questionPool(AttemptMode $mode, ?Booklet $booklet, ?Subject $subject, ?Difficulty $difficulty = null): Builder
    {
        $query = Question::query()->active()->with('options');

        if ($booklet) {
            // Questions linked to the booklet itself or to any of its topics.
            $query->where('booklet_id', $booklet->getKey());
        } else {
            $query->where('subject_id', $subject->getKey())->where('link_type', QuestionLinkType::None);
        }

        if ($source = $mode->source()) {
            $query->where('source', $source);
        }

        if ($difficulty) {
            $query->where('difficulty', $difficulty);
        }

        // Questions with fewer than 2 options or no correct answer are not playable.
        return $query->whereHas('options', null, '>=', 2)->whereHas('correctOption');
    }

    /**
     * Question counts behind the booklet's bottom buttons.
     *
     * @return array{ministerial: int, teacher: int, quiz: array<string, int>}
     */
    public function bookletStats(Booklet $booklet): array
    {
        $quiz = [];
        foreach (Difficulty::cases() as $difficulty) {
            $quiz[$difficulty->value] = $this->questionPool(AttemptMode::Quiz, $booklet, null, $difficulty)->count();
        }

        return [
            'ministerial' => $this->questionPool(AttemptMode::Ministerial, $booklet, null)->count(),
            'teacher' => $this->questionPool(AttemptMode::Teacher, $booklet, null)->count(),
            'quiz' => $quiz,
        ];
    }

    private function ensureInProgress(Attempt $attempt): void
    {
        if ($attempt->isSubmitted()) {
            throw ValidationException::withMessages(['attempt' => 'تم إرسال هذه المحاولة مسبقاً.']);
        }
    }
}
