<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class AssetServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('asset.manager', function ($app) {
            return new \App\Services\AssetManager($app['config']);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure CDN URL if enabled
        if (Config::get('assets.cdn.enabled')) {
            $cdnUrl = Config::get('assets.cdn.url');
            if ($cdnUrl) {
                Config::set('app.asset_url', $cdnUrl);
            }
        }

        // Share asset configuration with views
        View::composer('*', function ($view) {
            $view->with('assetConfig', [
                'cdn_enabled' => Config::get('assets.cdn.enabled', false),
                'cdn_url' => Config::get('assets.cdn.url', ''),
                'versioning' => Config::get('assets.versioning.enabled', true),
                'optimization' => Config::get('assets.optimization', []),
            ]);
        });

        // Register asset helper macros
        $this->registerAssetMacros();
    }

    /**
     * Register asset helper macros
     */
    private function registerAssetMacros(): void
    {
        // Macro for versioned assets
        URL::macro('versionedAsset', function ($path) {
            $config = Config::get('assets.versioning');
            
            if (!$config['enabled']) {
                return asset($path);
            }

            $strategy = $config['strategy'];
            $fullPath = public_path($path);
            
            if (!file_exists($fullPath)) {
                return asset($path);
            }

            switch ($strategy) {
                case 'hash':
                    $hash = substr(md5_file($fullPath), 0, 8);
                    return asset($path . '?v=' . $hash);
                
                case 'timestamp':
                    $timestamp = filemtime($fullPath);
                    return asset($path . '?v=' . $timestamp);
                
                case 'manifest':
                    return $this->getManifestAsset($path);
                
                default:
                    return asset($path);
            }
        });

        // Macro for critical CSS
        URL::macro('criticalCss', function ($path) {
            $criticalPath = public_path('css/critical/' . $path);
            
            if (file_exists($criticalPath)) {
                return '<style>' . file_get_contents($criticalPath) . '</style>';
            }
            
            return '';
        });

        // Macro for preload links
        URL::macro('preloadLink', function ($path, $type = 'script') {
            $url = URL::versionedAsset($path);
            $as = $type === 'style' ? 'style' : 'script';
            
            return '<link rel="preload" href="' . $url . '" as="' . $as . '">';
        });
    }

    /**
     * Get asset from Vite manifest
     */
    private function getManifestAsset($path): string
    {
        $manifestPath = public_path('build/manifest.json');
        
        if (!file_exists($manifestPath)) {
            return asset($path);
        }

        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if (isset($manifest[$path])) {
            return asset('build/' . $manifest[$path]['file']);
        }

        return asset($path);
    }
}