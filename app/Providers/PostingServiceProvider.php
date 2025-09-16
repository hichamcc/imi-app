<?php

namespace App\Providers;

use App\Services\DeclarationService;
use App\Services\DriverService;
use App\Services\PostingApiService;
use Illuminate\Support\ServiceProvider;

class PostingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PostingApiService::class, function ($app) {
            return new PostingApiService();
        });

        $this->app->singleton(DriverService::class, function ($app) {
            return new DriverService($app->make(PostingApiService::class));
        });

        $this->app->singleton(DeclarationService::class, function ($app) {
            return new DeclarationService($app->make(PostingApiService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}