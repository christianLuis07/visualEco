<?php

namespace App\Providers;

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
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Login::class, function (\Illuminate\Auth\Events\Login $event) {
            activity()
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                    'source' => request()?->is('api/*') ? 'mobile' : 'website'
                ])
                ->log('User logged in');
        });
    }
}
