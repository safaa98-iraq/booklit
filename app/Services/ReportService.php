<?php

namespace App\Services;

use App\Enums\AttemptStatus;
use App\Models\AttemptAnswer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query builders behind the admin reports.
 */
class ReportService
{
    /**
     * Questions with how often they were answered and how often wrongly,
     * hardest first. Rows carry `answered_count`, `wrong_count`, `error_rate` (0–100).
     */
    public function questionErrorRates(): Builder
    {
        $submitted = fn (Builder $q) => $q->whereHas('attempt', fn (Builder $a) => $a->where('status', AttemptStatus::Submitted));

        $counted = Question::query()
            ->select('questions.*')
            ->withCount([
                'answers as answered_count' => $submitted,
                'answers as wrong_count' => fn (Builder $q) => $submitted($q)->where('is_correct', false),
            ]);

        // Wrap in a sub-select so the aggregates can be filtered/sorted portably (MySQL & SQLite).
        return Question::query()
            ->fromSub($counted, 'questions')
            ->select('questions.*')
            ->selectRaw('ROUND(wrong_count * 100.0 / answered_count, 1) as error_rate')
            ->where('answered_count', '>', 0)
            ->with(['subject.stage', 'booklet', 'topic']);
    }

    /**
     * Per-student, per-topic performance on submitted attempts.
     * Each row: user_id, topic_id, answered, correct, accuracy (0–100).
     */
    public function topicPerformance(): Builder
    {
        return AttemptAnswer::query()
            ->join('attempts', 'attempts.id', '=', 'attempt_answers.attempt_id')
            ->where('attempts.status', AttemptStatus::Submitted->value)
            ->whereNotNull('attempt_answers.topic_id')
            ->groupBy('attempts.user_id', 'attempt_answers.topic_id')
            ->selectRaw('MIN(attempt_answers.id) as id')
            ->selectRaw('attempts.user_id as user_id')
            ->selectRaw('attempt_answers.topic_id as topic_id')
            ->selectRaw('COUNT(*) as answered')
            ->selectRaw('SUM(CASE WHEN attempt_answers.is_correct THEN 1 ELSE 0 END) as correct')
            ->selectRaw('ROUND(SUM(CASE WHEN attempt_answers.is_correct THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as accuracy')
            ->with(['student', 'topic.booklet.subject']);
    }
}
