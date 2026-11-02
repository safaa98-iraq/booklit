<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttemptMode;
use App\Enums\Difficulty;
use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Booklet;
use App\Services\AttemptService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttemptController extends Controller
{
    public function __construct(
        private readonly AttemptService $attempts,
        private readonly SubscriptionService $access,
    ) {}

    public function store(Request $request, Booklet $booklet): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::enum(AttemptMode::class)],
            'difficulty' => ['nullable', 'required_if:mode,quiz', Rule::enum(Difficulty::class)],
        ], [], ['mode' => 'نوع الأسئلة', 'difficulty' => 'مستوى الصعوبة']);

        $booklet->load('subject.stage');
        $this->authorize('view', $booklet);
        $this->access->ensureAccess($request->user(), $booklet->subject);

        $attempt = $this->attempts->start(
            $request->user(),
            AttemptMode::from($data['mode']),
            $booklet,
            null,
            Difficulty::tryFrom((string) ($data['difficulty'] ?? '')),
        );

        return redirect()->route('student.attempts.show', $attempt);
    }

    /** Solving page while in progress; results page once submitted. */
    public function show(Request $request, Attempt $attempt): View
    {
        $this->authorize('view', $attempt);

        $attempt->load(['booklet', 'answers.question.options']);
        if ($attempt->isSubmitted()) {
            $attempt->load('answers.question.anchors.block.topic');
        }

        return view($attempt->isSubmitted() ? 'student.attempt-result' : 'student.attempt-solve', compact('attempt'));
    }

    /** Background autosave of one answer (lets the student resume later). */
    public function saveAnswer(Request $request, Attempt $attempt): JsonResponse
    {
        $this->authorize('update', $attempt);
        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'option_id' => ['nullable', 'integer'],
        ]);

        $this->attempts->saveAnswers($attempt, [[
            'question_id' => (int) $data['question_id'],
            'option_id' => isset($data['option_id']) ? (int) $data['option_id'] : null,
        ]]);

        return response()->json(['saved' => true]);
    }

    /** "إرسال": score and show results. */
    public function submit(Request $request, Attempt $attempt): RedirectResponse
    {
        $this->authorize('update', $attempt);
        $request->validate(['answers' => ['sometimes', 'array'], 'answers.*' => ['nullable', 'integer']]);

        $answers = collect($request->input('answers', []))
            ->map(fn ($optionId, $questionId) => ['question_id' => (int) $questionId, 'option_id' => $optionId ? (int) $optionId : null])
            ->values()
            ->all();

        $this->attempts->submit($attempt, $answers);

        return redirect()->route('student.attempts.show', $attempt);
    }
}
