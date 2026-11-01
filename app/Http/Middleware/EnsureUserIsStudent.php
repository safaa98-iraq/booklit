<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStudent()) {
            return response()->json(['message' => 'هذه الواجهة مخصصة لحسابات الطلاب فقط.', 'code' => 'not_student'], 403);
        }

        if (! $user->is_active) {
            $user->currentAccessToken()?->delete();

            return response()->json(['message' => 'تم إيقاف هذا الحساب. يرجى التواصل مع الإدارة.', 'code' => 'account_disabled'], 403);
        }

        return $next($request);
    }
}
