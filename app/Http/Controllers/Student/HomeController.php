<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Enums\AttemptStatus;
use App\Models\Booklet;
use App\Models\Question;
use App\Models\Stage;
use App\Models\Subject;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private readonly SubscriptionService $access) {}

    /** Visitors get the landing page; students their dashboard and subject grid. */
    public function index(Request $request): View
    {
        if (! $request->user('student')) {
            return view('student.landing', [
                'stages' => Stage::active()->ordered()->get(['id', 'name']),
                'stats' => [
                    'stages' => Stage::active()->count(),
                    'booklets' => Booklet::published()->count(),
                    'questions' => Question::active()->count(),
                ],
            ]);
        }

        $stages = Stage::active()->ordered()
            ->with(['subjects' => fn ($q) => $q->active()->with('stage')])
            ->get();

        $stageId = $request->integer('stage') ?: $request->session()->get('stage_id');
        $stage = $stages->firstWhere('id', $stageId) ?? $stages->first();
        if ($stage) {
            $request->session()->put('stage_id', $stage->id);
        }

        $student = $request->user('student');
        $locked = $stage
            ? $stage->subjects->mapWithKeys(fn (Subject $s) => [$s->id => $this->access->isLocked($student, $s)])
            : collect();

        $submitted = $student->attempts()->where('status', AttemptStatus::Submitted);
        $summary = [
            'attempts' => (clone $submitted)->count(),
            'average' => (int) round((float) (clone $submitted)->avg('score')),
            'subscriptions' => $student->subscriptions()->currentlyActive()->count(),
        ];
        $lastBooklet = $student->attempts()->whereNotNull('booklet_id')->latest('id')->first()?->booklet;

        return view('student.home', compact('stages', 'stage', 'locked', 'summary', 'lastBooklet'));
    }

    /** Subject → its booklet (directly when there is only one). */
    public function subject(Request $request, Subject $subject): View|RedirectResponse
    {
        $subject->load('stage');
        $this->authorize('view', $subject);
        $this->access->ensureAccess($request->user(), $subject);

        $booklets = $subject->booklets()->published()->get();

        if ($booklets->count() === 1) {
            return redirect()->route('student.booklets.show', $booklets->first());
        }

        return view('student.subject', compact('subject', 'booklets'));
    }
}
