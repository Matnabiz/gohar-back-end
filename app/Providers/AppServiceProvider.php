<?php

namespace App\Providers;

use App\Services\MellatGateway;
use Illuminate\Support\Facades\Log;
use App\Models\Comment;
use App\Observers\CommentObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MellatGateway::class, function ($app) {
            return new MellatGateway();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Comment::observe(CommentObserver::class);
    }
}
