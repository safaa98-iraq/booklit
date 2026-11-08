<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * This is a personal notebook: there is no login. Every request acts as the
 * single owner — the first user in the database, created on first visit.
 */
class UseOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $owner = User::query()->oldest('id')->first() ?? User::create([
            'name' => 'صاحب الدفتر',
            'email' => 'owner@kakeibo.local',
            'password' => Str::random(40),
        ]);

        Auth::setUser($owner);

        return $next($request);
    }
}
