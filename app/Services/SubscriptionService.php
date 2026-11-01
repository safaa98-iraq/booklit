<?php

namespace App\Services;

use App\Exceptions\SubjectLockedException;
use App\Models\Stage;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Decides which subjects a student may open, and activates paid access.
 */
class SubscriptionService
{
    /** Seconds a computed set of grants may be reused (covers one request rendering many subjects). */
    private const MEMO_TTL = 5;

    /**
     * Short-lived memo per User instance, so listing N subjects costs one query
     * without ever serving stale grants to a long-lived process.
     *
     * @var \WeakMap<User, array{at: int, grants: array{stages: list<int>, subjects: list<int>}}>
     */
    private \WeakMap $memo;

    public function __construct()
    {
        $this->memo = new \WeakMap;
    }

    public function canAccessSubject(User $user, Subject $subject): bool
    {
        if (! $user->isStudent()) {
            return true; // staff preview everything
        }

        if (! $subject->requiresSubscription()) {
            return true;
        }

        $grants = $this->activeGrants($user);

        return in_array($subject->getKey(), $grants['subjects'], true)
            || in_array($subject->stage_id, $grants['stages'], true);
    }

    /** @throws SubjectLockedException */
    public function ensureAccess(User $user, Subject $subject): void
    {
        if (! $this->canAccessSubject($user, $subject)) {
            throw new SubjectLockedException;
        }
    }

    public function isLocked(User $user, Subject $subject): bool
    {
        return ! $this->canAccessSubject($user, $subject);
    }

    /**
     * Stage and subject IDs unlocked by currently active subscriptions.
     *
     * @return array{stages: list<int>, subjects: list<int>}
     */
    public function activeGrants(User $user): array
    {
        $now = now()->getTimestamp();
        $cached = $this->memo[$user] ?? null;

        if ($cached && $now - $cached['at'] < self::MEMO_TTL) {
            return $cached['grants'];
        }

        $subs = $user->subscriptions()->currentlyActive()->get(['stage_id', 'subject_id']);

        $grants = [
            'stages' => $subs->pluck('stage_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all(),
            'subjects' => $subs->pluck('subject_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all(),
        ];

        $this->memo[$user] = ['at' => $now, 'grants' => $grants];

        return $grants;
    }

    /** Latest expiry among active subscriptions covering this subject. */
    public function accessExpiresAt(User $user, Subject $subject): ?CarbonInterface
    {
        return $user->subscriptions()->currentlyActive()
            ->where(fn ($q) => $q->where('subject_id', $subject->getKey())->orWhere('stage_id', $subject->stage_id))
            ->orderByDesc('expires_at')
            ->first()?->expires_at;
    }

    /**
     * Activate a student for a whole stage and/or a list of subjects.
     *
     * @param  list<int>  $subjectIds
     * @return Collection<int, Subscription>
     */
    public function activate(
        User $student,
        ?int $stageId,
        array $subjectIds,
        CarbonInterface $expiresAt,
        ?User $activatedBy = null,
        ?CarbonInterface $startsAt = null,
        ?float $amount = null,
        ?string $notes = null,
    ): Collection {
        if (! $student->isStudent()) {
            throw new \InvalidArgumentException('Subscriptions can only be granted to students.');
        }

        if ($stageId === null && $subjectIds === []) {
            throw new \InvalidArgumentException('Choose a stage or at least one subject.');
        }

        $startsAt ??= now();

        $created = DB::transaction(function () use ($student, $stageId, $subjectIds, $expiresAt, $activatedBy, $startsAt, $amount, $notes) {
            $rows = collect();
            $base = [
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'activated_by' => $activatedBy?->getKey(),
                'amount' => $amount,
                'notes' => $notes,
            ];

            if ($stageId !== null) {
                Stage::findOrFail($stageId);
                $rows->push($student->subscriptions()->create($base + ['stage_id' => $stageId]));
            }

            foreach (array_unique($subjectIds) as $subjectId) {
                Subject::findOrFail($subjectId);
                $rows->push($student->subscriptions()->create($base + ['subject_id' => (int) $subjectId]));
            }

            return $rows;
        });

        unset($this->memo[$student]);

        return $created;
    }

    public function revoke(Subscription $subscription): void
    {
        $subscription->update(['revoked_at' => now()]);
        $this->memo = new \WeakMap;
    }
}
