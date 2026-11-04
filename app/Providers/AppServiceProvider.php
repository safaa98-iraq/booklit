<?php

namespace App\Providers;

use App\Models\Booklet;
use App\Models\Topic;
use App\Models\User;
use App\Services\SettingsService;
use App\Services\SubscriptionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        // Short morph names for favorites ("booklet" / "topic").
        Relation::morphMap(['booklet' => Booklet::class, 'topic' => Topic::class]);

        // Admins can do everything; policies only decide for editors/students.
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(10)->by('ip:'.$request->ip()),
            Limit::perMinute(5)->by('phone:'.$request->input('phone')),
        ]);

        // The signed-in student is available to every student page.
        View::composer('student.*', fn ($view) => $view->with('student', auth('student')->user()));

        RateLimiter::for('attempts', fn (Request $request) => Limit::perMinute(30)->by((string) $request->user()?->getKey()));
    }
}
