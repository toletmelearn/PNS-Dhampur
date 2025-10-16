<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class OptimizeAssets
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only apply optimizations to asset requests
        if ($this->isAssetRequest($request)) {
            $this->applyAssetOptimizations($response, $request);
        }

        return $response;
    }

    /**
     * Check if the request is for a static asset
     */
    private function isAssetRequest(Request $request): bool
    {
        $path = $request->getPathInfo();
        $assetExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf', 'eot'];
        
        foreach ($assetExtensions as $ext) {
            if (str_ends_with($path, '.' . $ext)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Apply asset optimization headers
     */
    private function applyAssetOptimizations($response, Request $request): void
    {
        $config = Config::get('assets');
        
        // Caching headers
        if ($config['caching']['enabled']) {
            $maxAge = $config['caching']['max_age'];
            $response->headers->set('Cache-Control', "public, max-age={$maxAge}, immutable");
            
            if ($config['caching']['etag']) {
                $etag = md5($response->getContent());
                $response->headers->set('ETag', '"' . $etag . '"');
            }
            
            if ($config['caching']['last_modified']) {
                $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            }
        }

        // Compression headers
        if ($config['compression']['enabled']) {
            $acceptEncoding = $request->header('Accept-Encoding', '');
            
            if ($config['compression']['brotli'] && str_contains($acceptEncoding, 'br')) {
                $response->headers->set('Content-Encoding', 'br');
                $response->headers->set('Vary', 'Accept-Encoding');
            } elseif ($config['compression']['gzip'] && str_contains($acceptEncoding, 'gzip')) {
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->set('Vary', 'Accept-Encoding');
            }
        }

        // Security headers for assets
        if ($config['security']['cors_enabled']) {
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization');
        }

        // Content type optimization
        $this->setOptimalContentType($response, $request);
    }

    /**
     * Set optimal content type based on file extension
     */
    private function setOptimalContentType($response, Request $request): void
    {
        $path = $request->getPathInfo();
        $contentTypes = [
            '.css' => 'text/css; charset=utf-8',
            '.js' => 'application/javascript; charset=utf-8',
            '.woff2' => 'font/woff2',
            '.woff' => 'font/woff',
            '.ttf' => 'font/ttf',
            '.svg' => 'image/svg+xml',
            '.png' => 'image/png',
            '.jpg' => 'image/jpeg',
            '.jpeg' => 'image/jpeg',
            '.gif' => 'image/gif',
        ];

        foreach ($contentTypes as $ext => $type) {
            if (str_ends_with($path, $ext)) {
                $response->headers->set('Content-Type', $type);
                break;
            }
        }
    }
}