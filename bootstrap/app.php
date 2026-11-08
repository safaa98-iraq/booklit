<?php

use App\Http\Middleware\UseOwner;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Render / load balancers terminate TLS: trust X-Forwarded-* so URLs are https.
        $middleware->trustProxies(at: '*');

        // Personal notebook: no login, every page belongs to the owner.
        $middleware->web(append: [UseOwner::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
