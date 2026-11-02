<?php

namespace App\Http\Middleware;

use App\Services\DeviceBindingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every browser a permanent device ID (cookie) and, for a signed-in
 * student, enforces that this browser is the account's bound device.
 */
class EnsureStudentWebDevice
{
    public const COOKIE = 'booklit_device';

    public function __construct(private readonly DeviceBindingService $devices) {}

    public static function deviceId(Request $request): string
    {
        return (string) $request->attributes->get('device_id');
    }

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->cookie(self::COOKIE);
        if (! is_string($id) || strlen($id) < 20) {
            $id = Str::random(40);
            Cookie::queue(self::COOKIE, $id, 60 * 24 * 365 * 5);
        }
        $request->attributes->set('device_id', 'web-'.$id);

        $guard = Auth::guard('student');
        $user = $guard->user();

        if ($user) {
            $problem = match (true) {
                ! $user->isStudent() => 'هذا الموقع مخصص لحسابات الطلاب.',
                ! $user->is_active => 'تم إيقاف هذا الحساب. يرجى التواصل مع الإدارة.',
                ! $this->devices->isBoundTo($user, self::deviceId($request)) => 'هذا الحساب مرتبط بجهاز آخر. يرجى التواصل مع الإدارة لإعادة تعيين الجهاز.',
                default => null,
            };

            if ($problem) {
                $guard->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('student.login')->withErrors(['phone' => $problem]);
            }

            $this->devices->touch($user, self::deviceId($request));
        }

        return $next($request);
    }
}
