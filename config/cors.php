<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => env('APP_ENV') === 'production' ? 
        explode(',', env('CORS_ALLOWED_ORIGINS', 'https://pnsdhampur.com,https://app.pnsdhampur.com')) : 
        ['http://localhost:3000', 'http://localhost:8000', 'http://127.0.0.1:8000', 'http://localhost:5173'],

    'allowed_origins_patterns' => env('APP_ENV') === 'production' ? 
        ['/^https:\/\/.*\.pnsdhampur\.com$/'] : 
        ['/^http:\/\/localhost:\d+$/', '/^http:\/\/127\.0\.0\.1:\d+$/'],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-Socket-ID',
        'Cache-Control',
        'Pragma',
        'X-API-Key'
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'X-Total-Count',
        'X-Page-Count',
        'X-Per-Page'
    ],

    'max_age' => env('APP_ENV') === 'production' ? 86400 : 0, // 24 hours in production

    'supports_credentials' => true,

];
