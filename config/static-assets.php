<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Static Asset Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for static asset optimization,
    | caching, and delivery in the PNS-Dhampur School Management System.
    |
    */

    'optimization' => [
        /*
        |--------------------------------------------------------------------------
        | Asset Minification
        |--------------------------------------------------------------------------
        |
        | Enable or disable minification of CSS and JavaScript files.
        | This should be enabled in production for better performance.
        |
        */
        'minify_css' => env('ASSET_MINIFY_CSS', true),
        'minify_js' => env('ASSET_MINIFY_JS', true),
        'minify_html' => env('ASSET_MINIFY_HTML', false),

        /*
        |--------------------------------------------------------------------------
        | Asset Compression
        |--------------------------------------------------------------------------
        |
        | Configure compression settings for static assets.
        |
        */
        'gzip_enabled' => env('ASSET_GZIP_ENABLED', true),
        'brotli_enabled' => env('ASSET_BROTLI_ENABLED', true),
        'compression_level' => env('ASSET_COMPRESSION_LEVEL', 6),

        /*
        |--------------------------------------------------------------------------
        | Image Optimization
        |--------------------------------------------------------------------------
        |
        | Settings for automatic image optimization and format conversion.
        |
        */
        'image_optimization' => [
            'enabled' => env('IMAGE_OPTIMIZATION_ENABLED', true),
            'quality' => env('IMAGE_QUALITY', 85),
            'webp_conversion' => env('WEBP_CONVERSION_ENABLED', true),
            'avif_conversion' => env('AVIF_CONVERSION_ENABLED', false),
            'max_width' => env('IMAGE_MAX_WIDTH', 1920),
            'max_height' => env('IMAGE_MAX_HEIGHT', 1080),
        ],
    ],

    'caching' => [
        /*
        |--------------------------------------------------------------------------
        | Browser Caching
        |--------------------------------------------------------------------------
        |
        | Configure browser caching headers for different asset types.
        | Values are in seconds.
        |
        */
        'browser_cache' => [
            'css' => env('CACHE_CSS_TTL', 31536000), // 1 year
            'js' => env('CACHE_JS_TTL', 31536000), // 1 year
            'images' => env('CACHE_IMAGES_TTL', 2592000), // 30 days
            'fonts' => env('CACHE_FONTS_TTL', 31536000), // 1 year
            'documents' => env('CACHE_DOCUMENTS_TTL', 86400), // 1 day
        ],

        /*
        |--------------------------------------------------------------------------
        | CDN Configuration
        |--------------------------------------------------------------------------
        |
        | Configure Content Delivery Network settings for asset delivery.
        |
        */
        'cdn' => [
            'enabled' => env('CDN_ENABLED', false),
            'url' => env('CDN_URL', ''),
            'assets_path' => env('CDN_ASSETS_PATH', 'assets'),
            'pull_zone' => env('CDN_PULL_ZONE', ''),
            'purge_on_deploy' => env('CDN_PURGE_ON_DEPLOY', true),
        ],

        /*
        |--------------------------------------------------------------------------
        | Server-side Caching
        |--------------------------------------------------------------------------
        |
        | Configure server-side caching for processed assets.
        |
        */
        'server_cache' => [
            'enabled' => env('ASSET_SERVER_CACHE_ENABLED', true),
            'driver' => env('ASSET_CACHE_DRIVER', 'file'),
            'ttl' => env('ASSET_CACHE_TTL', 86400), // 24 hours
            'path' => storage_path('framework/cache/assets'),
        ],
    ],

    'lazy_loading' => [
        /*
        |--------------------------------------------------------------------------
        | Lazy Loading Configuration
        |--------------------------------------------------------------------------
        |
        | Configure lazy loading behavior for images and components.
        |
        */
        'enabled' => env('LAZY_LOADING_ENABLED', true),
        'threshold' => env('LAZY_LOADING_THRESHOLD', '50px'),
        'placeholder' => env('LAZY_LOADING_PLACEHOLDER', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjOTk5Ij5Mb2FkaW5nLi4uPC90ZXh0Pjwvc3ZnPg=='),
        'fade_in_duration' => env('LAZY_FADE_DURATION', 300),
        'intersection_margin' => env('LAZY_INTERSECTION_MARGIN', '50px 0px'),
    ],

    'preloading' => [
        /*
        |--------------------------------------------------------------------------
        | Resource Preloading
        |--------------------------------------------------------------------------
        |
        | Configure which resources should be preloaded for better performance.
        |
        */
        'critical_css' => env('PRELOAD_CRITICAL_CSS', true),
        'fonts' => env('PRELOAD_FONTS', true),
        'hero_images' => env('PRELOAD_HERO_IMAGES', true),
        'dns_prefetch' => [
            'enabled' => env('DNS_PREFETCH_ENABLED', true),
            'domains' => [
                'fonts.googleapis.com',
                'fonts.gstatic.com',
                'cdnjs.cloudflare.com',
            ],
        ],
    ],

    'security' => [
        /*
        |--------------------------------------------------------------------------
        | Asset Security Headers
        |--------------------------------------------------------------------------
        |
        | Configure security headers for static assets.
        |
        */
        'content_security_policy' => [
            'enabled' => env('ASSET_CSP_ENABLED', true),
            'script_src' => "'self' 'unsafe-inline' 'unsafe-eval'",
            'style_src' => "'self' 'unsafe-inline' fonts.googleapis.com",
            'img_src' => "'self' data: blob:",
            'font_src' => "'self' fonts.gstatic.com",
        ],

        'cors' => [
            'enabled' => env('ASSET_CORS_ENABLED', true),
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'HEAD'],
            'allowed_headers' => ['Content-Type', 'Authorization'],
        ],
    ],

    'monitoring' => [
        /*
        |--------------------------------------------------------------------------
        | Asset Performance Monitoring
        |--------------------------------------------------------------------------
        |
        | Configure monitoring and analytics for asset performance.
        |
        */
        'enabled' => env('ASSET_MONITORING_ENABLED', false),
        'track_load_times' => env('TRACK_ASSET_LOAD_TIMES', false),
        'track_cache_hits' => env('TRACK_CACHE_HITS', false),
        'performance_budget' => [
            'max_css_size' => env('MAX_CSS_SIZE', 100), // KB
            'max_js_size' => env('MAX_JS_SIZE', 200), // KB
            'max_image_size' => env('MAX_IMAGE_SIZE', 500), // KB
            'max_total_size' => env('MAX_TOTAL_ASSETS_SIZE', 2000), // KB
        ],
    ],

    'development' => [
        /*
        |--------------------------------------------------------------------------
        | Development Settings
        |--------------------------------------------------------------------------
        |
        | Settings specific to development environment.
        |
        */
        'hot_reload' => env('ASSET_HOT_RELOAD', true),
        'source_maps' => env('ASSET_SOURCE_MAPS', true),
        'cache_busting' => env('ASSET_CACHE_BUSTING', true),
        'debug_mode' => env('ASSET_DEBUG_MODE', false),
    ],

    'file_types' => [
        /*
        |--------------------------------------------------------------------------
        | Supported File Types
        |--------------------------------------------------------------------------
        |
        | Define which file types are supported for different optimizations.
        |
        */
        'images' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'avif'],
        'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'],
        'videos' => ['mp4', 'webm', 'ogg', 'avi', 'mov'],
        'fonts' => ['woff', 'woff2', 'ttf', 'eot', 'otf'],
        'stylesheets' => ['css', 'scss', 'sass', 'less'],
        'scripts' => ['js', 'ts', 'jsx', 'tsx'],
    ],

    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | Asset Paths
        |--------------------------------------------------------------------------
        |
        | Define paths for different types of assets.
        |
        */
        'public' => public_path(),
        'build' => public_path('build'),
        'storage' => storage_path('app/public'),
        'uploads' => storage_path('app/public/uploads'),
        'cache' => storage_path('framework/cache/assets'),
        'temp' => storage_path('app/temp'),
    ],

    'versioning' => [
        /*
        |--------------------------------------------------------------------------
        | Asset Versioning
        |--------------------------------------------------------------------------
        |
        | Configure asset versioning for cache busting.
        |
        */
        'enabled' => env('ASSET_VERSIONING_ENABLED', true),
        'strategy' => env('ASSET_VERSION_STRATEGY', 'hash'), // hash, timestamp, manual
        'manifest_path' => public_path('build/manifest.json'),
        'version_param' => env('ASSET_VERSION_PARAM', 'v'),
    ],

    'cleanup' => [
        /*
        |--------------------------------------------------------------------------
        | Asset Cleanup
        |--------------------------------------------------------------------------
        |
        | Configure automatic cleanup of old or unused assets.
        |
        */
        'enabled' => env('ASSET_CLEANUP_ENABLED', true),
        'old_versions_retention' => env('ASSET_OLD_VERSIONS_RETENTION', 7), // days
        'temp_files_retention' => env('ASSET_TEMP_FILES_RETENTION', 1), // days
        'unused_files_detection' => env('ASSET_UNUSED_FILES_DETECTION', false),
    ],
];