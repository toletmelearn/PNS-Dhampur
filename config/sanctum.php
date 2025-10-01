<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    */
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', '127.0.0.1,127.0.0.1:8000,localhost,localhost:8000')),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guard
    |--------------------------------------------------------------------------
    */
    'guard' => ['sanctum'],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],

];
