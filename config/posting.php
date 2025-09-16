<?php

return [

    /*
    |--------------------------------------------------------------------------
    | EU Road Transport Posting Declaration API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the EU Road Transport
    | Posting Declaration API integration.
    |
    */

    'api' => [
        'base_url' => env('POSTING_API_BASE_URL', 'https://api.postingdeclaration.eu'),
        'key' => env('POSTING_API_KEY'),
        'operator_id' => env('POSTING_API_OPERATOR_ID'),
        'timeout' => env('POSTING_API_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Define the API endpoints for different resources
    |
    */

    'endpoints' => [
        'drivers' => '/drivers',
        'declarations' => '/declarations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for caching API responses
    |
    */

    'cache' => [
        'enabled' => env('POSTING_API_CACHE_ENABLED', true),
        'ttl' => env('POSTING_API_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'posting_api_',
    ],

];