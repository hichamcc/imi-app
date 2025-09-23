<?php

namespace App\Providers;

use App\Services\PostingApiService;
use Illuminate\Support\ServiceProvider;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PostingApiService::class, function ($app) {
            $apiService = new PostingApiService();

            // Set user-specific credentials if user is authenticated and has valid credentials
            if (auth()->check()) {
                $user = auth()->user();
                \Log::info('ApiServiceProvider - User authenticated', [
                    'user_id' => $user->id,
                    'has_valid_credentials' => $user->hasValidApiCredentials(),
                    'api_key' => $user->api_key ? substr($user->api_key, 0, 8) . '...' : 'NULL',
                    'operator_id' => $user->api_operator_id ?: 'NULL',
                    'base_url' => $user->api_base_url ?: 'NULL'
                ]);

                if ($user->hasValidApiCredentials()) {
                    $apiService->setUserCredentials(
                        $user->api_base_url,
                        $user->api_key,
                        $user->api_operator_id
                    );
                    \Log::info('ApiServiceProvider - User credentials set');
                } else {
                    \Log::warning('ApiServiceProvider - User does not have valid credentials');
                }
            } else {
                \Log::info('ApiServiceProvider - No authenticated user');
            }

            return $apiService;
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
