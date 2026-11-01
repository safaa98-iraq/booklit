<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttemptMode;
use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReviewQuestionsRequest;
use App\Http\Resources\BookletResource;
use App\Http\Resources\QuestionReviewResource;
use App\Models\Booklet;
use App\Models\Subject;
use App\Services\AttemptService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookletController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $access,
        private readonly AttemptService $attempts,
    ) {}

    /** Reader view: topics + content blocks + bottom-button configuration. */
    public function show(Request $request, Booklet $booklet): BookletResource
    {
        $booklet->load('subject.stage');
        $this->authorize('view', $booklet);
        $this->access->ensureAccess($request->user(), $booklet->subject);

        $booklet->load(['topics.blocks']);

        return new BookletResource($booklet, $this->questionStats($booklet));
    }

    /** "أظهر الحل" for a booklet's MCQ questions of one source. */
    public function review(ReviewQuestionsRequest $request, Booklet $booklet): AnonymousResourceCollection
    {
        $booklet->load('subject.stage');
        $this->authorize('view', $booklet);
        $this->access->ensureAccess($request->user(), $booklet->subject);

        $mode = $request->source() === QuestionSource::Ministerial ? AttemptMode::Ministerial : AttemptMode::Teacher;

        $questions = $this->attempts->questionPool($mode, $booklet, null)
            ->with('anchors.block.topic')
            ->get()
            ->shuffle();

        return QuestionReviewResource::collection($questions);
    }

    /** "أظهر الحل" for a subject's standalone question bank. */
    public function reviewSubjectBank(ReviewQuestionsRequest $request, Subject $subject): AnonymousResourceCollection
    {
        $subject->load('stage');
        $this->authorize('view', $subject);
        $this->access->ensureAccess($request->user(), $subject);

        $mode = $request->source() === QuestionSource::Ministerial ? AttemptMode::Ministerial : AttemptMode::Teacher;

        $questions = $this->attempts->questionPool($mode, null, $subject)
            ->with('anchors.block.topic')
            ->get()
            ->shuffle();

        return QuestionReviewResource::collection($questions);
    }

    /** @return array{ministerial: int, teacher: int, quiz: array<string, int>} */
    private function questionStats(Booklet $booklet): array
    {
        $quiz = [];
        foreach (Difficulty::cases() as $difficulty) {
            $quiz[$difficulty->value] = $this->attempts->questionPool(AttemptMode::Quiz, $booklet, null, $difficulty)->count();
        }

        return [
            'ministerial' => $this->attempts->questionPool(AttemptMode::Ministerial, $booklet, null)->count(),
            'teacher' => $this->attempts->questionPool(AttemptMode::Teacher, $booklet, null)->count(),
            'quiz' => $quiz,
        ];
    }
}
