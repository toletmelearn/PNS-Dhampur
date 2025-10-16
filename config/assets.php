<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Asset Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for static asset management, CDN integration,
    | and production optimizations.
    |
    */

    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'url' => env('CDN_URL', ''),
        'key' => env('CDN_KEY', ''),
        'secret' => env('CDN_SECRET', ''),
        'region' => env('CDN_REGION', 'us-east-1'),
        'bucket' => env('CDN_BUCKET', ''),
    ],

    'compression' => [
        'enabled' => env('ASSET_COMPRESSION', true),
        'gzip' => true,
        'brotli' => true,
    ],

    'caching' => [
        'enabled' => env('ASSET_CACHING', true),
        'max_age' => env('ASSET_CACHE_MAX_AGE', 31536000), // 1 year
        'etag' => true,
        'last_modified' => true,
    ],

    'optimization' => [
        'minify_css' => env('MINIFY_CSS', true),
        'minify_js' => env('MINIFY_JS', true),
        'optimize_images' => env('OPTIMIZE_IMAGES', true),
        'lazy_loading' => env('LAZY_LOADING', true),
    ],

    'versioning' => [
        'enabled' => env('ASSET_VERSIONING', true),
        'strategy' => env('ASSET_VERSION_STRATEGY', 'hash'), // hash, timestamp, manifest
    ],

    'preload' => [
        'critical_css' => true,
        'fonts' => [
            '/fonts/inter.woff2',
            '/fonts/roboto.woff2',
        ],
        'scripts' => [
            'app.js',
        ],
    ],

    'security' => [
        'csp_enabled' => env('CSP_ENABLED', true),
        'sri_enabled' => env('SRI_ENABLED', true),
        'cors_enabled' => env('CORS_ENABLED', true),
    ],
];