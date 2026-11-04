<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttemptMode;
use App\Enums\QuestionSource;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\GuardsContent;
use App\Models\Booklet;
use App\Models\ContentBlock;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\AttemptService;
use App\Services\ProgressService;
use App\Services\SettingsService;
use App\Support\Snippet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kindle-like reader: one topic per page, saved reading position,
 * topic-scoped quizzes, and the "Edit" tools.
 */
class ReaderController extends Controller
{
    use GuardsContent;

    public function __construct(
        private readonly AttemptService $attempts,
        private readonly ProgressService $progress,
    ) {}

    /** Opening a booklet resumes at the last topic read (or the first). */
    public function booklet(Request $request, Booklet $booklet): RedirectResponse
    {
        $this->guardBooklet($request, $booklet);

        $topicId = $request->user()->topicProgress()->where('booklet_id', $booklet->id)->latest('last_read_at')->value('topic_id')
            ?? $booklet->topics()->value('id');

        abort_unless($topicId, 404, 'لا يوجد محتوى في هذه الملزمة بعد.');

        return redirect()->route('student.topics.show', $topicId);
    }

    /** Old links: a subject opens in the library. */
    public function subject(Subject $subject): RedirectResponse
    {
        return redirect()->route('student.library.subject', $subject);
    }

    public function show(Request $request, Topic $topic, SettingsService $settings): View
    {
        $booklet = $topic->booklet;
        $this->guardBooklet($request, $booklet);
        $student = $request->user();

        $topic->load('blocks');
        $topics = $booklet->topics()->get(['id', 'title', 'sort_order', 'booklet_id']);
        $index = $topics->search(fn ($t) => $t->id === $topic->id);

        // Passage highlight coming from a result page or search: ?hl=<block-uuid>:<start>:<end>
        $anchor = null;
        if (preg_match('/^([0-9a-f-]{36}):(\d+):(\d+)$/', (string) $request->query('hl'), $m)) {
            $anchor = ['block' => $m[1], 'start' => (int) $m[2], 'end' => (int) $m[3]];
        }

        $highlights = $student->highlights()->whereIn('content_block_id', $topic->blocks->pluck('id'))->get()->groupBy('content_block_id');
        $saved = $student->topicProgress()->where('topic_id', $topic->id)->first();
        $done = $student->topicProgress()->where('booklet_id', $booklet->id)->whereNotNull('completed_at')->pluck('topic_id')->all();

        return view('student.reader', [
            'booklet' => $booklet,
            'topic' => $topic,
            'topics' => $topics,
            'index' => $index,
            'prev' => $topics[$index - 1] ?? null,
            'next' => $topics[$index + 1] ?? null,
            'anchor' => $anchor,
            'highlights' => $highlights,
            'resumeAt' => $anchor ? null : ($saved?->scroll_percent ?? 0),
            'done' => $done,
            'stats' => $this->attempts->topicStats($topic),
            'fourth' => $settings->get('fourth_button'),
            'isFavorite' => $student->favorites()->where('favoritable_type', 'topic')->where('favoritable_id', $topic->id)->exists(),
            'fontSize' => (int) $student->preference('font_size'),
        ]);
    }

    /** Reading heartbeat: scroll position + seconds read since the last beat. */
    public function track(Request $request, Topic $topic): JsonResponse
    {
        $this->guardBooklet($request, $topic->booklet);
        $data = $request->validate([
            'scroll' => ['required', 'integer', 'between:0,100'],
            'seconds' => ['required', 'integer', 'between:0,600'],
        ]);

        $p = $this->progress->track($request->user(), $topic, $data['scroll'], $data['seconds']);

        return response()->json(['completed' => $p->completed_at !== null]);
    }

    /** "Edit → بحث": search inside this booklet. */
    public function search(Request $request, Booklet $booklet): JsonResponse
    {
        $this->guardBooklet($request, $booklet);
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $results = ContentBlock::query()
            ->whereIn('topic_id', $booklet->topics()->select('id'))
            ->where('plain_text', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%')
            ->with('topic:id,title')
            ->limit(25)->get()
            ->map(function (ContentBlock $b) use ($q) {
                $pos = mb_stripos($b->plain_text, $q);

                return [
                    'topic' => $b->topic->title,
                    'snippet' => Snippet::around($b->plain_text, $q),
                    'url' => route('student.topics.show', [$b->topic_id, 'hl' => "{$b->uuid}:{$pos}:".($pos + mb_strlen($q))]).'#b-'.$b->uuid,
                ];
            });

        return response()->json(['results' => $results]);
    }

    /** "أظهر الحل" for the current topic's MCQs. */
    public function review(Request $request, Topic $topic, string $source): View
    {
        $this->guardBooklet($request, $topic->booklet);
        $mode = QuestionSource::from($source) === QuestionSource::Ministerial ? AttemptMode::Ministerial : AttemptMode::Teacher;

        $questions = $this->attempts->questionPool($mode, null, null, null, $topic)
            ->with('anchors.block.topic')->get()->shuffle();

        return view('student.review', compact('topic', 'questions', 'mode'));
    }
}
