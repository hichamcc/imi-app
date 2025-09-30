<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Lab404\Impersonate\Events\TakeImpersonation;
use Lab404\Impersonate\Events\LeaveImpersonation;

class ClearApiCacheOnImpersonation
{
    /**
     * Handle user impersonation started event
     */
    public function handleTakeImpersonation(TakeImpersonation $event): void
    {
        $this->clearApiCache($event->impersonator, $event->impersonated);

        Log::info('API cache cleared for impersonation', [
            'admin_id' => $event->impersonator->id,
            'admin_name' => $event->impersonator->name,
            'user_id' => $event->impersonated->id,
            'user_name' => $event->impersonated->name,
        ]);
    }

    /**
     * Handle user impersonation ended event
     */
    public function handleLeaveImpersonation(LeaveImpersonation $event): void
    {
        $this->clearApiCache($event->impersonator, $event->impersonated);

        Log::info('API cache cleared after leaving impersonation', [
            'admin_id' => $event->impersonator->id,
            'admin_name' => $event->impersonator->name,
            'user_id' => $event->impersonated->id,
            'user_name' => $event->impersonated->name,
        ]);
    }

    /**
     * Clear API-related cache for both users
     */
    private function clearApiCache($admin, $user): void
    {
        // Clear cache for both admin and impersonated user
        $this->clearUserApiCache($admin);
        $this->clearUserApiCache($user);

        // Clear any shared cache that might contain API responses
        Cache::forget('api_drivers_cache');
        Cache::forget('api_declarations_cache');
        Cache::forget('api_trucks_cache');
    }

    /**
     * Clear API cache for a specific user
     */
    private function clearUserApiCache($user): void
    {
        $userId = $user->id;

        // Clear user-specific API cache
        Cache::forget("api_drivers_user_{$userId}");
        Cache::forget("api_declarations_user_{$userId}");
        Cache::forget("api_trucks_user_{$userId}");
        Cache::forget("api_stats_user_{$userId}");

        // Clear any API tokens or session cache
        Cache::forget("api_token_user_{$userId}");
        Cache::forget("api_credentials_user_{$userId}");
    }
}
