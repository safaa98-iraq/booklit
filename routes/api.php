<?php

use App\Http\Controllers\Api\V1\AttemptController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookletController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('settings', SettingsController::class)->name('settings');

    Route::middleware('throttle:auth')->prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
    });

    Route::middleware(['auth:sanctum', 'student', 'device.bound'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [ProfileController::class, 'show'])->name('me');

        Route::get('stages', [CatalogController::class, 'stages'])->name('stages.index');
        Route::get('subjects', [CatalogController::class, 'subjects'])->name('subjects.index');
        Route::get('subjects/{subject}', [CatalogController::class, 'subject'])->name('subjects.show');
        Route::get('subjects/{subject}/question-bank/review', [BookletController::class, 'reviewSubjectBank'])->name('subjects.bank.review');

        Route::get('booklets/{booklet}', [BookletController::class, 'show'])->name('booklets.show');
        Route::get('booklets/{booklet}/questions/review', [BookletController::class, 'review'])->name('booklets.review');

        Route::get('attempts', [AttemptController::class, 'index'])->name('attempts.index');
        Route::post('attempts', [AttemptController::class, 'store'])->middleware('throttle:attempts')->name('attempts.store');
        Route::get('attempts/{attempt}', [AttemptController::class, 'show'])->name('attempts.show');
        Route::put('attempts/{attempt}/answers', [AttemptController::class, 'saveAnswers'])->name('attempts.answers');
        Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submit'])->name('attempts.submit');
    });
});
