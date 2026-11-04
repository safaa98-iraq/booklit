<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __construct(private readonly ProgressService $progress) {}

    public function index(Request $request): View
    {
        $student = $request->user();

        return view('student.progress', [
            'overall' => $this->progress->overall($student),
            'subjects' => $this->progress->subjects($student),
            'stats' => $this->progress->stats($student),
        ]);
    }

    /** Progress inside one subject: booklets → topics. */
    public function subject(Request $request, Subject $subject): View
    {
        $student = $request->user();
        $booklets = $subject->booklets()->published()->with('topics')->get();
        $rows = $student->topicProgress()->whereIn('booklet_id', $booklets->pluck('id'))->get()->keyBy('topic_id');

        return view('student.progress-subject', [
            'subject' => $subject->load('stage'),
            'booklets' => $booklets->map(fn ($b) => ['booklet' => $b] + $this->progress->bookletProgress($student, $b)),
            'rows' => $rows,
        ]);
    }
}
