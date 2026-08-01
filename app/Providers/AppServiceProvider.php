<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('midtrans-notification', function (Request $request) {
            $ip = $request->ip() ?? 'unknown';

            return [
                // Hard cap per IP
                Limit::perMinute(30)->by('midtrans-ip:'.$ip),
                // Burst pendek: max 10 / 10 detik
                Limit::perSecond(2)->by('midtrans-burst:'.$ip),
            ];
        });
    }
}
