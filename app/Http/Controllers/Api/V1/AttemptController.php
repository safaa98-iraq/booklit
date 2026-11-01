<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SaveAnswersRequest;
use App\Http\Requests\Api\StartAttemptRequest;
use App\Http\Requests\Api\SubmitAttemptRequest;
use App\Http\Resources\AttemptResource;
use App\Models\Attempt;
use App\Services\AttemptService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttemptController extends Controller
{
    public function __construct(
        private readonly AttemptService $attempts,
        private readonly SubscriptionService $access,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $attempts = $request->user()->attempts()
            ->when($request->integer('booklet_id'), fn ($q, $id) => $q->where('booklet_id', $id))
            ->latest('id')
            ->paginate(20);

        return AttemptResource::collection($attempts);
    }

    public function store(StartAttemptRequest $request): JsonResponse
    {
        $booklet = $request->booklet();
        $subject = $booklet?->subject ?? $request->subject();

        if ($booklet) {
            $this->authorize('view', $booklet);
        } else {
            $this->authorize('view', $subject);
        }
        $this->access->ensureAccess($request->user(), $subject);

        $attempt = $this->attempts->start($request->user(), $request->mode(), $booklet, $booklet ? null : $subject, $request->difficulty());

        return (new AttemptResource($this->loadForDisplay($attempt)))->response()->setStatusCode(201);
    }

    public function show(Request $request, Attempt $attempt): AttemptResource
    {
        $this->authorize('view', $attempt);

        return new AttemptResource($this->loadForDisplay($attempt));
    }

    /** Save / change answers before submitting (lets the app resume later). */
    public function saveAnswers(SaveAnswersRequest $request, Attempt $attempt): AttemptResource
    {
        $attempt = $this->attempts->saveAnswers($attempt, $request->answers());

        return new AttemptResource($this->loadForDisplay($attempt));
    }

    /** "إرسال": score the attempt and return results with explanations and passage anchors. */
    public function submit(SubmitAttemptRequest $request, Attempt $attempt): AttemptResource
    {
        $attempt = $this->attempts->submit($attempt, $request->answers());

        return new AttemptResource($this->loadForDisplay($attempt));
    }

    private function loadForDisplay(Attempt $attempt): Attempt
    {
        $relations = ['answers.question.options'];

        if ($attempt->isSubmitted()) {
            $relations[] = 'answers.question.anchors.block.topic';
        }

        return $attempt->load($relations);
    }
}
