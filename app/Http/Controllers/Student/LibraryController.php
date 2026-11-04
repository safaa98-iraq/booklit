<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\GuardsContent;
use App\Models\Stage;
use App\Models\Subject;
use App\Services\ProgressService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryController extends Controller
{
    use GuardsContent;

    public function __construct(
        private readonly SubscriptionService $access,
        private readonly ProgressService $progress,
    ) {}

    /** Stages → subjects (paid ones shown with a small lock). */
    public function index(Request $request): View
    {
        $stages = Stage::active()->ordered()
            ->with(['subjects' => fn ($q) => $q->active()->with('stage')->withCount(['booklets' => fn ($b) => $b->published()])])
            ->get();

        $stage = $stages->firstWhere('id', $request->integer('stage') ?: $request->session()->get('stage_id')) ?? $stages->first();
        if ($stage) {
            $request->session()->put('stage_id', $stage->id);
        }

        $locked = $stage?->subjects->mapWithKeys(fn (Subject $s) => [$s->id => $this->access->isLocked($request->user(), $s)]) ?? collect();

        return view('student.library', compact('stages', 'stage', 'locked'));
    }

    /** A subject's booklets and the selected booklet's topics. */
    public function subject(Request $request, Subject $subject): View
    {
        $this->guardSubject($request, $subject);
        $student = $request->user();

        $booklets = $subject->booklets()->published()->withCount('topics')->get();
        $selected = $booklets->firstWhere('id', $request->integer('booklet')) ?? $booklets->first();
        $selected?->load('topics');

        $done = $selected
            ? $student->topicProgress()->where('booklet_id', $selected->id)->get()->keyBy('topic_id')
            : collect();

        $favorites = $student->favorites()->where('favoritable_type', 'booklet')->pluck('favoritable_id')->all();

        return view('student.library-subject', [
            'subject' => $subject,
            'booklets' => $booklets,
            'selected' => $selected,
            'done' => $done,
            'favorites' => $favorites,
            'progress' => $booklets->mapWithKeys(fn ($b) => [$b->id => $this->progress->bookletProgress($student, $b)]),
            'showTopics' => $request->has('booklet'), // on phones: booklets first, then topics
        ]);
    }
}
