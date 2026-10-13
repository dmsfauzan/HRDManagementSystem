<?php

namespace App\Providers;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(12)->letters()->mixedCase()->numbers());

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.strtolower((string) $request->input('email')));
        });

        RateLimiter::for('password', function (Request $request) {
            if (app()->environment('testing')) {
                return Limit::none();
            }

            return Limit::perMinute(3)->by($request->ip());
        });

        RateLimiter::for('reports', function (Request $request) {
            if (app()->environment('testing')) {
                return Limit::none();
            }

            $identifier = $request->user()?->id ?? $request->ip();

            return Limit::perMinute(20)->by((string) $identifier);
        });

        RateLimiter::for('attendance', function (Request $request) {
            if (app()->environment('testing')) {
                return Limit::none();
            }

            return Limit::perMinute(30)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        Event::listen(Login::class, function (Login $event) {
            AuditLogger::log('auth.login', $event->user, [], ['email' => $event->user->email]);
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                AuditLogger::log('auth.logout', $event->user, [], ['email' => $event->user->email]);
            }
        });
    }
}
