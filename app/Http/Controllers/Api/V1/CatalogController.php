<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\StageResource;
use App\Http\Resources\SubjectResource;
use App\Models\Stage;
use App\Models\Subject;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Home screen: stages and their subjects (with lock state). */
class CatalogController extends Controller
{
    public function __construct(private readonly SubscriptionService $access) {}

    public function stages(): AnonymousResourceCollection
    {
        $stages = Stage::active()->ordered()
            ->with(['subjects' => fn ($q) => $q->active()->with('stage')])
            ->get();

        return StageResource::collection($stages);
    }

    public function subjects(Request $request): AnonymousResourceCollection
    {
        $request->validate(['stage_id' => ['nullable', 'integer']]);

        $subjects = Subject::active()
            ->whereHas('stage', fn ($q) => $q->active())
            ->when($request->integer('stage_id'), fn ($q, $id) => $q->where('stage_id', $id))
            ->with('stage')
            ->orderBy('stage_id')->orderBy('sort_order')
            ->get();

        return SubjectResource::collection($subjects);
    }

    /** Opening a subject: locked subjects answer 403 with the Telegram contact payload. */
    public function subject(Request $request, Subject $subject): SubjectResource
    {
        $subject->load('stage');
        $this->authorize('view', $subject);
        $this->access->ensureAccess($request->user(), $subject);

        return new SubjectResource($subject->load(['booklets' => fn ($q) => $q->published()]));
    }
}
