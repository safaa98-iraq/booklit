<?php

namespace App\Http\Middleware;

use App\Services\DeviceBindingService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every authenticated student request must come from the device the account
 * is bound to (sent as the X-Device-Id header) using a token issued to that device.
 */
class EnsureDeviceIsBound
{
    public const HEADER = 'X-Device-Id';

    public function __construct(private readonly DeviceBindingService $devices) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $deviceId = (string) $request->header(self::HEADER, '');
        $token = $user?->currentAccessToken();

        $tokenMatches = ! $token instanceof PersonalAccessToken
            || hash_equals((string) $token->name, $deviceId);

        if (! $user || ! $tokenMatches || ! $this->devices->isBoundTo($user, $deviceId)) {
            return response()->json([
                'message' => 'هذا الحساب مرتبط بجهاز آخر. يرجى التواصل مع الإدارة لإعادة تعيين الجهاز.',
                'code' => 'device_mismatch',
            ], 409);
        }

        $this->devices->touch($user, $deviceId);

        return $next($request);
    }
}
