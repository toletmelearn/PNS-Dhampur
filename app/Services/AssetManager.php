<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AssetManager
{
    protected $config;
    protected $cdnDisk;

    public function __construct($config)
    {
        $this->config = $config;
        $this->setupCdnDisk();
    }

    /**
     * Setup CDN disk configuration
     */
    private function setupCdnDisk(): void
    {
        if (Config::get('assets.cdn.enabled')) {
            Config::set('filesystems.disks.cdn', [
                'driver' => 's3',
                'key' => Config::get('assets.cdn.key'),
                'secret' => Config::get('assets.cdn.secret'),
                'region' => Config::get('assets.cdn.region'),
                'bucket' => Config::get('assets.cdn.bucket'),
                'url' => Config::get('assets.cdn.url'),
                'endpoint' => Config::get('assets.cdn.endpoint'),
                'use_path_style_endpoint' => false,
                'throw' => false,
            ]);

            $this->cdnDisk = Storage::disk('cdn');
        }
    }

    /**
     * Upload assets to CDN
     */
    public function uploadToCdn(string $localPath, string $remotePath = null): bool
    {
        if (!Config::get('assets.cdn.enabled') || !$this->cdnDisk) {
            return false;
        }

        try {
            $remotePath = $remotePath ?: basename($localPath);
            $content = file_get_contents($localPath);
            
            $result = $this->cdnDisk->put($remotePath, $content, [
                'visibility' => 'public',
                'CacheControl' => 'max-age=31536000, public, immutable',
                'ContentType' => $this->getMimeType($localPath),
            ]);

            if ($result) {
                Log::info("Asset uploaded to CDN: {$remotePath}");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("CDN upload failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync build directory to CDN
     */
    public function syncBuildToCdn(): bool
    {
        if (!Config::get('assets.cdn.enabled')) {
            return false;
        }

        $buildPath = public_path('build');
        
        if (!is_dir($buildPath)) {
            Log::warning("Build directory not found: {$buildPath}");
            return false;
        }

        $success = true;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($buildPath)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $relativePath = str_replace($buildPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                
                if (!$this->uploadToCdn($file->getPathname(), 'build/' . $relativePath)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Generate critical CSS
     */
    public function generateCriticalCss(string $url, string $outputPath): bool
    {
        try {
            // This would typically use a tool like Puppeteer or similar
            // For now, we'll create a placeholder implementation
            $criticalCss = $this->extractCriticalCss($url);
            
            $criticalDir = dirname($outputPath);
            if (!is_dir($criticalDir)) {
                mkdir($criticalDir, 0755, true);
            }

            file_put_contents($outputPath, $criticalCss);
            Log::info("Critical CSS generated: {$outputPath}");
            
            return true;
        } catch (\Exception $e) {
            Log::error("Critical CSS generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimize images
     */
    public function optimizeImage(string $imagePath): bool
    {
        if (!Config::get('assets.optimization.optimize_images')) {
            return true;
        }

        try {
            $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    return $this->optimizeJpeg($imagePath);
                case 'png':
                    return $this->optimizePng($imagePath);
                case 'webp':
                    return $this->optimizeWebp($imagePath);
                default:
                    return true;
            }
        } catch (\Exception $e) {
            Log::error("Image optimization failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get MIME type for file
     */
    private function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Extract critical CSS (placeholder implementation)
     */
    private function extractCriticalCss(string $url): string
    {
        // This is a placeholder. In a real implementation, you would:
        // 1. Use Puppeteer or similar to load the page
        // 2. Extract above-the-fold CSS
        // 3. Minify and return the critical CSS
        
        return "/* Critical CSS for {$url} - Generated automatically */\n";
    }

    /**
     * Optimize JPEG images
     */
    private function optimizeJpeg(string $imagePath): bool
    {
        // Placeholder for JPEG optimization
        // In production, you might use ImageMagick or similar
        return true;
    }

    /**
     * Optimize PNG images
     */
    private function optimizePng(string $imagePath): bool
    {
        // Placeholder for PNG optimization
        // In production, you might use pngquant or similar
        return true;
    }

    /**
     * Optimize WebP images
     */
    private function optimizeWebp(string $imagePath): bool
    {
        // Placeholder for WebP optimization
        return true;
    }
}