<?php

namespace App\Providers;

use App\Services\StockNotificationService;
use Illuminate\Support\Facades\Gate;
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
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });

        View::composer('partials._navbar', function ($view) {
            $notifications = app(StockNotificationService::class)->notifications();

            $view->with([
                'navbarNotifications' => $notifications->take(5),
                'navbarNotificationCount' => $notifications->count(),
            ]);
        });
    }
}
