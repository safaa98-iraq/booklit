<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SettingsService;
use App\Services\SubscriptionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        // Admins can do everything; policies only decide for editors/students.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(10)->by('ip:'.$request->ip()),
            Limit::perMinute(5)->by('phone:'.$request->input('phone')),
        ]);

        RateLimiter::for('attempts', fn (Request $request) => Limit::perMinute(30)->by((string) $request->user()?->getKey()));
    }
}
