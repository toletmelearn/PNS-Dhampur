<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "apc", "array", "database", "file",
    |            "memcached", "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

        // Custom cache stores for different data types
        'sessions' => [
            'driver' => 'redis',
            'connection' => 'sessions',
        ],

        'views' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => 'views',
        ],

        'queries' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => 'queries',
        ],

        'reports' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => 'reports',
        ],

        'api' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'prefix' => 'api',
        ],

        'long_term' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/long_term'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a RAM based store such as APC or Memcached, there might
    | be other applications utilizing the same cache. So, we'll specify a
    | value to get prefixed to all our keys so we can avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache'),

    /*
    |--------------------------------------------------------------------------
    | Cache Tags
    |--------------------------------------------------------------------------
    |
    | Cache tags allow you to tag related pieces of cached data and then
    | flush all cached values that have been assigned a tag. This is
    | useful for grouping related cached values for easier management.
    |
    */

    'tags' => [
        'students' => 'students_cache',
        'teachers' => 'teachers_cache',
        'attendance' => 'attendance_cache',
        'exams' => 'exams_cache',
        'fees' => 'fees_cache',
        'reports' => 'reports_cache',
        'notifications' => 'notifications_cache',
        'system' => 'system_cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL Settings
    |--------------------------------------------------------------------------
    |
    | Default TTL values for different types of cached data
    |
    */

    'ttl' => [
        'default' => 3600, // 1 hour
        'short' => 300,    // 5 minutes
        'medium' => 1800,  // 30 minutes
        'long' => 86400,   // 24 hours
        'reports' => 7200, // 2 hours
        'static' => 604800, // 1 week
    ],

];
