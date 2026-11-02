<?php

namespace App\Http\Controllers\Student;

use App\Exceptions\DeviceMismatchException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureStudentWebDevice;
use App\Http\Requests\Student\LoginRequest;
use App\Http\Requests\Student\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Services\DeviceBindingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly DeviceBindingService $devices,
    ) {}

    public function showLogin(): View
    {
        return view('student.auth.login');
    }

    public function showRegister(): View
    {
        return view('student.auth.register');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $this->auth->verifyCredentials($request->validated('phone'), $request->validated('password'));

        return $this->signIn($request, $user);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(fn () => $this->auth->createStudent($request->validated()));

        return $this->signIn($request, $user);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.login');
    }

    /** Bind this browser as the account's device, then start the session. */
    private function signIn(Request $request, User $user): RedirectResponse
    {
        try {
            $this->devices->bind($user, [
                'device_id' => EnsureStudentWebDevice::deviceId($request),
                'device_name' => $this->browserName((string) $request->userAgent()),
                'platform' => 'web',
                'ip_address' => $request->ip(),
            ]);
        } catch (DeviceMismatchException $e) {
            return back()->withInput($request->only('phone'))->withErrors(['phone' => $e->getMessage()]);
        }

        Auth::guard('student')->login($user, remember: true);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('student.home'));
    }

    private function browserName(string $agent): string
    {
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'متصفح',
        };
        $os = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone'), str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => '',
        };

        return trim("{$browser} — {$os}", ' —');
    }
}
