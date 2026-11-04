<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProgressService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly ProgressService $progress) {}

    public function index(Request $request): View
    {
        $student = $request->user();

        return view('student.profile.index', [
            'stats' => $this->progress->stats($student),
            'subscriptions' => $student->subscriptions()->currentlyActive()->with(['stage', 'subject'])->get(),
        ]);
    }

    public function edit(): View
    {
        return view('student.profile.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $student = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['nullable', 'email:rfc', 'max:191', Rule::unique('users', 'email')->ignore($student)],
        ], [], ['name' => 'الاسم', 'email' => 'البريد الإلكتروني']);

        $student->update($data);

        return redirect()->route('student.profile')->with('status', 'تم حفظ بيانات الحساب.');
    }

    public function password(): View
    {
        return view('student.profile.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['current_password' => 'كلمة المرور الحالية', 'password' => 'كلمة المرور الجديدة']);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        $request->user()->update(['password' => $data['password']]);

        return redirect()->route('student.profile')->with('status', 'تم تغيير كلمة المرور.');
    }

    /** Notifications / Appearance / Language: simple preference pages. */
    public function preferences(Request $request, string $page): View
    {
        abort_unless(in_array($page, ['notifications', 'appearance', 'language'], true), 404);

        return view('student.profile.preferences', ['page' => $page]);
    }

    public function savePreferences(Request $request, string $page): RedirectResponse|JsonResponse
    {
        $rules = [
            'notifications' => ['notifications' => ['nullable', 'boolean']],
            'appearance' => ['font_size' => ['required', 'integer', 'between:14,26']],
            'language' => ['language' => ['required', Rule::in(['ar'])]],
        ][$page] ?? abort(404);

        $data = $request->validate($rules);
        if ($page === 'notifications') {
            $data['notifications'] = $request->boolean('notifications');
        }

        $this->savePrefs($request->user(), $data);

        return $request->expectsJson()
            ? response()->json(['saved' => true])
            : redirect()->route('student.profile')->with('status', 'تم حفظ الإعدادات.');
    }

    /** Reader "A− / A+" buttons save the text size without leaving the page. */
    public function fontSize(Request $request)
    {
        $data = $request->validate(['font_size' => ['required', 'integer', 'between:14,26']]);
        $this->savePrefs($request->user(), $data);

        return response()->json(['saved' => true]);
    }

    public function recent(Request $request): View
    {
        $items = $request->user()->topicProgress()->with(['topic', 'booklet.subject'])->latest('last_read_at')->limit(30)->get()
            ->filter(fn ($p) => $p->topic && $p->booklet);

        return view('student.profile.recent', compact('items'));
    }

    public function attempts(Request $request): View
    {
        $attempts = $request->user()->attempts()->with(['booklet', 'topic', 'subject'])->latest('id')->paginate(20);

        return view('student.profile.attempts', compact('attempts'));
    }

    public function help(SettingsService $settings): View
    {
        return view('student.profile.help', ['settings' => $settings]);
    }

    public function about(): View
    {
        return view('student.profile.about');
    }

    private function savePrefs(User $user, array $values): void
    {
        $user->forceFill(['preferences' => array_replace($user->preferences ?? [], $values)])->save();
    }
}
