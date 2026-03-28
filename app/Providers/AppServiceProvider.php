<?php

namespace App\Providers;

use App\Models\GameSession;
use App\Observers\GameSessionObserver;
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
        GameSession::observe(GameSessionObserver::class);
    }
}
