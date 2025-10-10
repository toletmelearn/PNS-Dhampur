<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,localhost:8000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort()
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. If this value is null, personal access tokens do
    | not expire. This won't tweak the lifetime of first-party sessions.
    |
    */

    'expiration' => env('SANCTUM_TOKEN_EXPIRATION', 60 * 24), // 24 hours default

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Personal Access Token Settings
    |--------------------------------------------------------------------------
    |
    | Configure personal access token behavior including abilities and
    | automatic cleanup of expired tokens.
    |
    */

    'personal_access_tokens' => [
        'abilities' => [
            'api:read',
            'api:write',
            'api:delete',
            'attendance:view',
            'attendance:manage',
            'inventory:view',
            'inventory:manage',
            'reports:view',
            'reports:generate',
            'admin:full'
        ],
        
        'cleanup_expired' => env('SANCTUM_CLEANUP_EXPIRED', true),
        
        'prune_revoked_after_days' => env('SANCTUM_PRUNE_REVOKED_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for token-based authentication to prevent
    | abuse and ensure API stability.
    |
    */

    'rate_limiting' => [
        'enabled' => env('SANCTUM_RATE_LIMITING', true),
        
        'max_attempts_per_minute' => env('SANCTUM_MAX_ATTEMPTS', 60),
        
        'lockout_duration' => env('SANCTUM_LOCKOUT_DURATION', 60), // seconds
        
        'track_by_token' => true,
        
        'track_by_ip' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Additional security configurations for token management and validation.
    |
    */

    'security' => [
        'require_https' => env('SANCTUM_REQUIRE_HTTPS', env('APP_ENV') === 'production'),
        
        'token_entropy' => env('SANCTUM_TOKEN_ENTROPY', 40), // bytes of randomness
        
        'hash_tokens' => env('SANCTUM_HASH_TOKENS', true),
        
        'revoke_other_tokens_on_login' => env('SANCTUM_REVOKE_OTHER_TOKENS', false),
        
        'max_tokens_per_user' => env('SANCTUM_MAX_TOKENS_PER_USER', 10),
        
        'token_name_validation' => [
            'required' => true,
            'max_length' => 255,
            'allowed_characters' => '/^[a-zA-Z0-9\s\-_\.]+$/',
        ],
    ],

];
