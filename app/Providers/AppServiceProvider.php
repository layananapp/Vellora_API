<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Broadcast::routes(['middleware' => ['jwt']]);

        // ── Login: maks 5x per menit per IP dan per email ───────────────────
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($request->input('email', 'guest')),
            ];
        });

        // ── Register: maks 3x per menit per IP ──────────────────────────────
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // ── OTP: maks 3x per jam per email, maks 1x per menit per IP ────────
        RateLimiter::for('otp', function (Request $request) {
            return [
                Limit::perHour(3)->by($request->input('email', 'guest')),
                Limit::perMinute(1)->by($request->ip()),
            ];
        });
    }
}
