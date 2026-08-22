<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
        // すべてのビューで未読通知数を自動的に共有する
        View::composer('*', function ($view) {
            $count = 0;
            if (Auth::check()) {
                $count = Auth::user()->unreadNotifications->count();
            }
            $view->with('unreadNotificationCount', $count);
        });
    }
}
