<?php

namespace App\Providers;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        Event::listen(Login::class, function (Login $event): void {
            LoginActivity::create([
                'user_id' => $event->user->getAuthIdentifier(),
                'name' => $event->user->name,
                'role' => $event->user->role?->name,
                'activity_type' => 'login',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'occurred_at' => now(),
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            LoginActivity::create([
                'user_id' => $event->user?->getAuthIdentifier(),
                'name' => $event->user?->name ?? 'Unknown',
                'role' => $event->user?->role?->name,
                'activity_type' => 'logout',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'occurred_at' => now(),
            ]);
        });
    }
}
