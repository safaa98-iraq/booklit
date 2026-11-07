<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\MonthController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [MonthController::class, 'home'])->name('home');
    Route::get('/months', [MonthController::class, 'index'])->name('months.index');

    Route::pattern('ym', '\d{4}-\d{2}');
    Route::get('/months/{ym}', [MonthController::class, 'show'])->name('months.show');
    Route::put('/months/{ym}/plan', [MonthController::class, 'updatePlan'])->name('months.plan');
    Route::put('/months/{ym}/reflection', [MonthController::class, 'updateReflection'])->name('months.reflection');

    Route::post('/entries', [EntryController::class, 'store'])->name('entries.store');
    Route::get('/entries/{entry}', [EntryController::class, 'show'])->name('entries.show');
    Route::put('/entries/{entry}', [EntryController::class, 'update'])->name('entries.update');
    Route::delete('/entries/{entry}', [EntryController::class, 'destroy'])->name('entries.destroy');

    Route::post('/entries/{entry}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->name('settings.destroy');
});
