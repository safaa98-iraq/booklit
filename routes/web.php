<?php

use Illuminate\Support\Facades\Route;

// The student web app (Flutter) lives in public/app; the admin panel at /admin.
Route::redirect('/', '/app/');

// Serve the web app's entry point for /app and any client-side path under it
// (Apache serves the static files directly; this covers `php artisan serve` too).
Route::get('/app/{path?}', fn () => response()->file(public_path('app/index.html'), [
    'Cache-Control' => 'no-cache',
]))->where('path', '.*');
