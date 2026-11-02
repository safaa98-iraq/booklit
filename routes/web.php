<?php

use App\Http\Controllers\Student\AccountController;
use App\Http\Controllers\Student\AttemptController;
use App\Http\Controllers\Student\AuthController;
use App\Http\Controllers\Student\BookletController;
use App\Http\Controllers\Student\HomeController;
use App\Http\Middleware\EnsureStudentWebDevice;
use Illuminate\Support\Facades\Route;

/*
| Student web site (Arabic RTL). The admin/editor panel lives at /admin.
*/
Route::middleware(EnsureStudentWebDevice::class)->name('student.')->group(function () {
    Route::middleware('guest:student')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login.submit');
        Route::get('register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('register.submit');
    });

    Route::middleware('auth:student')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('account', AccountController::class)->name('account');

        Route::get('subjects/{subject}', [HomeController::class, 'subject'])->name('subjects.show');
        Route::get('booklets/{booklet}', [BookletController::class, 'show'])->name('booklets.show');
        Route::get('booklets/{booklet}/review/{source}', [BookletController::class, 'review'])
            ->whereIn('source', ['ministerial', 'teacher'])->name('booklets.review');

        Route::post('booklets/{booklet}/attempts', [AttemptController::class, 'store'])->middleware('throttle:attempts')->name('attempts.store');
        Route::get('attempts/{attempt}', [AttemptController::class, 'show'])->name('attempts.show');
        Route::post('attempts/{attempt}/answer', [AttemptController::class, 'saveAnswer'])->name('attempts.answer');
        Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submit'])->name('attempts.submit');
    });
});

// Old links to the paused Flutter web build.
Route::redirect('app/{any?}', '/')->where('any', '.*');
