<?php

use App\Http\Middleware\EnsureDeviceIsBound;
use App\Http\Middleware\EnsureUserIsStudent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'student' => EnsureUserIsStudent::class,
            'device.bound' => EnsureDeviceIsBound::class,
        ]);

        // Render / load balancers terminate TLS: trust X-Forwarded-* so URLs are https.
        $middleware->trustProxies(at: '*');

        // API guests get a JSON 401, never a redirect.
        $middleware->redirectGuestsTo(fn (Request $request) => match (true) {
            $request->is('api/*') => null,
            $request->is('admin', 'admin/*') => '/admin/login',
            default => route('student.login'),
        });
        // A signed-in student visiting /login goes to the home page.
        $middleware->redirectUsersTo(fn (Request $request) => $request->is('admin', 'admin/*') ? '/admin' : route('student.home'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The student app always gets JSON.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
    })->create();
