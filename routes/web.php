<?php

use App\Http\Controllers\Student\AttemptController;
use App\Http\Controllers\Student\AuthController;
use App\Http\Controllers\Student\FavoriteController;
use App\Http\Controllers\Student\HighlightController;
use App\Http\Controllers\Student\HomeController;
use App\Http\Controllers\Student\LibraryController;
use App\Http\Controllers\Student\ProfileController;
use App\Http\Controllers\Student\ProgressController;
use App\Http\Controllers\Student\ReaderController;
use App\Http\Middleware\EnsureStudentWebDevice;
use Illuminate\Support\Facades\Route;

/*
| Student web site (Arabic RTL, mobile first). The admin/editor panel lives at /admin.
*/
Route::middleware(EnsureStudentWebDevice::class)->name('student.')->group(function () {
    Route::middleware('guest:student')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login.submit');
        Route::get('register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('register.submit');
    });

    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::middleware('auth:student')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('search', [HomeController::class, 'search'])->name('search');
        Route::get('notifications', [HomeController::class, 'notifications'])->name('notifications');

        // Library: stages → subject → booklets → topics
        Route::get('library', [LibraryController::class, 'index'])->name('library');
        Route::get('library/subjects/{subject}', [LibraryController::class, 'subject'])->name('library.subject');
        Route::get('subjects/{subject}', [ReaderController::class, 'subject'])->name('subjects.show');

        // Reader: one topic per page
        Route::get('booklets/{booklet}', [ReaderController::class, 'booklet'])->name('booklets.show');
        Route::get('booklets/{booklet}/search', [ReaderController::class, 'search'])->name('booklets.search');
        Route::get('topics/{topic}', [ReaderController::class, 'show'])->name('topics.show');
        Route::post('topics/{topic}/progress', [ReaderController::class, 'track'])->middleware('throttle:120,1')->name('topics.progress');
        Route::get('topics/{topic}/review/{source}', [ReaderController::class, 'review'])
            ->whereIn('source', ['ministerial', 'teacher'])->name('topics.review');

        // Quizzes (scoped to the current topic)
        Route::post('topics/{topic}/attempts', [AttemptController::class, 'store'])->middleware('throttle:attempts')->name('attempts.store');
        Route::get('attempts/{attempt}', [AttemptController::class, 'show'])->name('attempts.show');
        Route::post('attempts/{attempt}/answer', [AttemptController::class, 'saveAnswer'])->name('attempts.answer');
        Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submit'])->name('attempts.submit');

        Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites');
        Route::post('favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
        Route::post('highlights', [HighlightController::class, 'store'])->name('highlights.store');
        Route::delete('highlights/{highlight}', [HighlightController::class, 'destroy'])->name('highlights.destroy');

        Route::get('progress', [ProgressController::class, 'index'])->name('progress');
        Route::get('progress/subjects/{subject}', [ProgressController::class, 'subject'])->name('progress.subject');

        Route::prefix('profile')->name('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'index']);
            Route::get('edit', [ProfileController::class, 'edit'])->name('.edit');
            Route::put('edit', [ProfileController::class, 'update'])->name('.update');
            Route::get('password', [ProfileController::class, 'password'])->name('.password');
            Route::put('password', [ProfileController::class, 'updatePassword'])->name('.password.update');
            Route::post('font-size', [ProfileController::class, 'fontSize'])->name('.font');
            Route::get('recent', [ProfileController::class, 'recent'])->name('.recent');
            Route::get('attempts', [ProfileController::class, 'attempts'])->name('.attempts');
            Route::get('help', [ProfileController::class, 'help'])->name('.help');
            Route::get('about', [ProfileController::class, 'about'])->name('.about');
            Route::get('{page}', [ProfileController::class, 'preferences'])->whereIn('page', ['notifications', 'appearance', 'language'])->name('.preferences');
            Route::put('{page}', [ProfileController::class, 'savePreferences'])->whereIn('page', ['notifications', 'appearance', 'language'])->name('.preferences.save');
        });
        Route::redirect('account', '/profile');
    });
});

// Old links to the paused Flutter web build.
Route::redirect('app/{any?}', '/')->where('any', '.*');
