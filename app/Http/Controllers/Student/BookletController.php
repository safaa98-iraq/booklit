<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttemptMode;
use App\Enums\QuestionSource;
use App\Http\Controllers\Controller;
use App\Models\Booklet;
use App\Services\AttemptService;
use App\Services\SettingsService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookletController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $access,
        private readonly AttemptService $attempts,
    ) {}

    /** Reader. `?hl=<block-uuid>:<start>:<end>` highlights a passage (from a result page). */
    public function show(Request $request, Booklet $booklet, SettingsService $settings): View
    {
        $this->guard($request, $booklet);
        $booklet->load('topics.blocks');

        $highlight = null;
        if (preg_match('/^([0-9a-f-]{36}):(\d+):(\d+)$/', (string) $request->query('hl'), $m)) {
            $highlight = ['block' => $m[1], 'start' => (int) $m[2], 'end' => (int) $m[3]];
        }

        return view('student.booklet', [
            'booklet' => $booklet,
            'stats' => $this->attempts->bookletStats($booklet),
            'fourth' => $settings->get('fourth_button'),
            'highlight' => $highlight,
        ]);
    }

    /** "أظهر الحل": questions with answers, without solving. */
    public function review(Request $request, Booklet $booklet, string $source): View
    {
        $this->guard($request, $booklet);
        $source = QuestionSource::from($source);
        $mode = $source === QuestionSource::Ministerial ? AttemptMode::Ministerial : AttemptMode::Teacher;

        $questions = $this->attempts->questionPool($mode, $booklet, null)
            ->with('anchors.block.topic')
            ->get()
            ->shuffle();

        return view('student.review', compact('booklet', 'questions', 'mode'));
    }

    private function guard(Request $request, Booklet $booklet): void
    {
        $booklet->load('subject.stage');
        $this->authorize('view', $booklet);
        $this->access->ensureAccess($request->user(), $booklet->subject);
    }
}
