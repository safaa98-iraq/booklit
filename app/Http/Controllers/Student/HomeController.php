<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Booklet;
use App\Models\ContentBlock;
use App\Models\Stage;
use App\Models\Topic;
use App\Services\ProgressService;
use App\Services\SubscriptionService;
use App\Support\Snippet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProgressService $progress,
        private readonly SubscriptionService $access,
    ) {}

    /** Greeting, Continue Reading, stage shortcuts, and booklets in progress. */
    public function index(Request $request): View|RedirectResponse
    {
        $student = $request->user('student');
        if (! $student) {
            return redirect()->route('student.login');
        }

        $last = $this->progress->lastRead($student);
        $continue = $last && $this->access->canAccessSubject($student, $last->booklet->subject)
            ? ['progress' => $last, 'booklet' => $this->progress->bookletProgress($student, $last->booklet)]
            : null;

        $inProgress = Booklet::published()
            ->whereIn('id', $student->topicProgress()->select('booklet_id'))
            ->with('subject.stage')
            ->get()
            ->map(fn (Booklet $b) => ['booklet' => $b] + $this->progress->bookletProgress($student, $b))
            ->sortByDesc('percent')
            ->take(6)
            ->values();

        return view('student.home', [
            'continue' => $continue,
            'stages' => Stage::active()->ordered()->withCount(['subjects' => fn ($q) => $q->active()])->get(),
            'inProgress' => $inProgress,
            'unread' => count($this->notifications($request)),
        ]);
    }

    /** Search booklets and topics by title, and topics by their content. */
    public function search(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $booklets = collect();
        $topics = collect();

        if (mb_strlen($q) >= 2) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $booklets = Booklet::published()->where('title', 'like', $like)->with('subject.stage')->limit(10)->get();

            $byContent = ContentBlock::where('plain_text', 'like', $like)->pluck('topic_id');
            $topics = Topic::where(fn ($w) => $w->where('title', 'like', $like)->orWhereIn('id', $byContent))
                ->whereHas('booklet', fn ($b) => $b->published())
                ->with(['booklet.subject.stage', 'blocks' => fn ($b) => $b->where('plain_text', 'like', $like)])
                ->limit(20)->get()
                ->map(function (Topic $topic) use ($q) {
                    $block = $topic->blocks->first();
                    $pos = $block ? mb_stripos($block->plain_text, $q) : false;

                    return [
                        'topic' => $topic,
                        'snippet' => $block ? Snippet::around($block->plain_text, $q, 50) : null,
                        'hl' => $block && $pos !== false ? "{$block->uuid}:{$pos}:".($pos + mb_strlen($q)) : null,
                    ];
                });
        }

        $student = $request->user();
        $lockedSubjects = collect($booklets->pluck('subject'))->merge($topics->pluck('topic.booklet.subject'))
            ->filter()->unique('id')
            ->mapWithKeys(fn ($s) => [$s->id => $this->access->isLocked($student, $s)]);

        return view('student.search', compact('q', 'booklets', 'topics', 'lockedSubjects'));
    }

    public function notifications(Request $request): array|View
    {
        $student = $request->user('student');
        $items = [];

        if ($student->preference('notifications')) {
            foreach ($student->subscriptions()->currentlyActive()->with(['stage', 'subject'])->get() as $sub) {
                $days = (int) now()->diffInDays($sub->expires_at, false);
                if ($days <= 14) {
                    $items[] = ['icon' => 'clock', 'title' => 'اشتراكك ينتهي قريباً', 'body' => ($sub->stage?->name ?? $sub->subject?->name)." — بعد {$days} يوم", 'date' => $sub->expires_at];
                }
                if ($sub->created_at->gt(now()->subDays(7))) {
                    $items[] = ['icon' => 'check', 'title' => 'تم تفعيل اشتراكك', 'body' => $sub->stage?->name ?? $sub->subject?->name, 'date' => $sub->created_at];
                }
            }
            foreach (Booklet::published()->where('created_at', '>', now()->subDays(14))->with('subject')->latest()->limit(5)->get() as $b) {
                $items[] = ['icon' => 'book', 'title' => 'ملزمة جديدة', 'body' => "{$b->title} — {$b->subject->name}", 'date' => $b->created_at, 'url' => route('student.booklets.show', $b)];
            }
        }

        usort($items, fn ($a, $b) => $b['date'] <=> $a['date']);

        return $request->routeIs('student.notifications') ? view('student.notifications', ['items' => $items]) : $items;
    }
}
