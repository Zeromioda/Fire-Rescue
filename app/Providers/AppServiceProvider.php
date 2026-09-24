<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use App\Models\LoginLog;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(Login::class, function ($event) {
            $agent = request()->header('User-Agent');
            $device = 'Desktop Terminal';

            if (preg_match('/(android|bb\d+|meego).+mobile|avail|blackberry|iphone|ipad|ipod/i', $agent)) {
                $device = 'Mobile Handheld';
            } elseif (preg_match('/tablet|ipad/i', $agent)) {
                $device = 'Field Tablet';
            }

            LoginLog::create([
                'user_id' => $event->user->id,
                'ip_address' => request()->ip(),
                'user_agent' => substr($agent, 0, 255),
                'device_type' => $device,
                'location' => 'BFAD Station 178 Console',
                'login_at' => now(),
            ]);
        });
    }
}