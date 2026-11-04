<?php

namespace App\Services;

use App\Enums\AttemptStatus;
use App\Models\AttemptAnswer;
use App\Models\Booklet;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\TopicProgress;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Reading progress, completion and study statistics — all computed from the
 * student's real activity (topic_progress rows and submitted attempts).
 */
class ProgressService
{
    /** Record a reading heartbeat from the reader page. */
    public function track(User $user, Topic $topic, int $scrollPercent, int $seconds): TopicProgress
    {
        $progress = TopicProgress::firstOrNew(['user_id' => $user->getKey(), 'topic_id' => $topic->getKey()]);
        $progress->booklet_id = $topic->booklet_id;
        $progress->scroll_percent = max(0, min(100, $scrollPercent));
        $progress->reading_seconds = (int) $progress->reading_seconds + max(0, min(60, $seconds));
        $progress->last_read_at = now();

        if (! $progress->completed_at && $progress->scroll_percent >= TopicProgress::COMPLETE_AT) {
            $progress->completed_at = now();
        }

        $progress->save();

        return $progress;
    }

    /** The last topic the student was reading (for "Continue Reading"). */
    public function lastRead(User $user): ?TopicProgress
    {
        return $user->topicProgress()
            ->whereHas('booklet', fn ($q) => $q->published())
            ->with(['topic', 'booklet.subject.stage'])
            ->latest('last_read_at')
            ->first();
    }

    /** @return array{completed: int, total: int, percent: int} */
    public function bookletProgress(User $user, Booklet $booklet): array
    {
        $total = $booklet->topics()->count();
        $completed = $user->topicProgress()->where('booklet_id', $booklet->getKey())->whereNotNull('completed_at')->count();

        return ['completed' => $completed, 'total' => $total, 'percent' => $total ? (int) round($completed / $total * 100) : 0];
    }

    /**
     * Per-subject progress for every subject the student has started reading.
     *
     * @return Collection<int, array{subject: Subject, completed: int, total: int, percent: int}>
     */
    public function subjects(User $user): Collection
    {
        $subjectIds = Booklet::whereIn('id', $user->topicProgress()->select('booklet_id'))->pluck('subject_id')->unique();
        $completedByBooklet = $user->topicProgress()->whereNotNull('completed_at')
            ->selectRaw('booklet_id, COUNT(*) as c')->groupBy('booklet_id')->pluck('c', 'booklet_id');

        return Subject::whereIn('id', $subjectIds)
            ->with(['stage', 'booklets' => fn ($q) => $q->published()->withCount('topics')])
            ->get()
            ->map(function (Subject $subject) use ($completedByBooklet) {
                $total = $subject->booklets->sum('topics_count');
                $completed = $subject->booklets->sum(fn ($b) => (int) ($completedByBooklet[$b->id] ?? 0));

                return [
                    'subject' => $subject,
                    'completed' => $completed,
                    'total' => $total,
                    'percent' => $total ? (int) round($completed / $total * 100) : 0,
                ];
            })
            ->sortByDesc('percent')
            ->values();
    }

    /** @return array{completed: int, total: int, percent: int, booklets_completed: int} */
    public function overall(User $user): array
    {
        $subjects = $this->subjects($user);
        $completed = $subjects->sum('completed');
        $total = $subjects->sum('total');

        $bookletsCompleted = $subjects->flatMap(fn ($row) => $row['subject']->booklets)
            ->filter(fn (Booklet $b) => $b->topics_count > 0 && $this->bookletProgress($user, $b)['completed'] >= $b->topics_count)
            ->count();

        return [
            'completed' => $completed,
            'total' => $total,
            'percent' => $total ? (int) round($completed / $total * 100) : 0,
            'booklets_completed' => $bookletsCompleted,
        ];
    }

    /** Profile statistics. @return array{booklets: int, accuracy: int, seconds: int} */
    public function stats(User $user): array
    {
        $answers = AttemptAnswer::query()
            ->whereHas('attempt', fn ($q) => $q->where('user_id', $user->getKey())->where('status', AttemptStatus::Submitted));
        $answered = (clone $answers)->count();
        $correct = (clone $answers)->where('is_correct', true)->count();

        return [
            'booklets' => $user->topicProgress()->distinct('booklet_id')->count('booklet_id'),
            'accuracy' => $answered ? (int) round($correct / $answered * 100) : 0,
            'seconds' => (int) $user->topicProgress()->sum('reading_seconds'),
        ];
    }

    public static function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return $hours > 0 ? "{$hours} س {$minutes} د" : "{$minutes} د";
    }
}
