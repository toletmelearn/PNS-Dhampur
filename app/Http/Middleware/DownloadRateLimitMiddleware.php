<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DownloadRateLimitMiddleware
{
    /**
     * Download limits per user role (downloads per hour)
     */
    private const ROLE_DOWNLOAD_LIMITS = [
        'super_admin' => 1000,
        'admin' => 500,
        'principal' => 300,
        'vice_principal' => 300,
        'teacher' => 200,
        'student' => 50,
        'guest' => 10
    ];
    
    /**
     * Bandwidth limits per user role (MB per hour)
     */
    private const ROLE_BANDWIDTH_LIMITS = [
        'super_admin' => 10240, // 10GB
        'admin' => 5120,        // 5GB
        'principal' => 2048,    // 2GB
        'vice_principal' => 2048, // 2GB
        'teacher' => 1024,      // 1GB
        'student' => 512,       // 512MB
        'guest' => 100          // 100MB
    ];
    
    /**
     * File type categories and their multipliers
     */
    private const FILE_TYPE_MULTIPLIERS = [
        'document' => 1.0,      // PDF, DOC, etc.
        'image' => 0.5,         // JPG, PNG, etc.
        'video' => 3.0,         // MP4, AVI, etc.
        'audio' => 2.0,         // MP3, WAV, etc.
        'archive' => 2.5,       // ZIP, RAR, etc.
        'executable' => 5.0,    // EXE, MSI, etc.
        'other' => 1.0
    ];
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $fileType
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $fileType = null)
    {
        $user = Auth::user();
        $ip = $request->ip();
        $userRole = $user ? $user->role : 'guest';
        
        // Detect file type if not provided
        $fileType = $fileType ?? $this->detectFileType($request);
        
        // Get limits based on user role and file type
        $downloadLimit = $this->getDownloadLimit($userRole, $fileType);
        $bandwidthLimit = $this->getBandwidthLimit($userRole, $fileType);
        
        // Create rate limiting keys
        $userDownloadKey = $user ? "downloads_user:{$user->id}" : "downloads_ip:{$ip}";
        $userBandwidthKey = $user ? "bandwidth_user:{$user->id}" : "bandwidth_ip:{$ip}";
        $globalDownloadKey = "downloads_global";
        $rapidDownloadKey = $user ? "rapid_downloads_user:{$user->id}" : "rapid_downloads_ip:{$ip}";
        
        // Check download count limit
        if (RateLimiter::tooManyAttempts($userDownloadKey, $downloadLimit)) {
            $this->logRateLimitExceeded('download_count', $userDownloadKey, $request, $fileType);
            return $this->rateLimitResponse($request, 'download', RateLimiter::availableIn($userDownloadKey));
        }
        
        // Check bandwidth limit
        $currentBandwidth = Cache::get($userBandwidthKey, 0);
        if ($currentBandwidth >= $bandwidthLimit * 1024 * 1024) { // Convert MB to bytes
            $this->logRateLimitExceeded('bandwidth', $userBandwidthKey, $request, $fileType);
            return $this->bandwidthLimitResponse($request, $bandwidthLimit);
        }
        
        // Check for rapid downloads (potential scraping)
        if (RateLimiter::tooManyAttempts($rapidDownloadKey, 10)) { // 10 downloads in 1 minute
            $this->logSuspiciousActivity($request, $fileType);
            return $this->suspiciousActivityResponse($request);
        }
        
        // Check global download limit (prevent system overload)
        $globalLimit = 10000; // 10k downloads per hour globally
        if (RateLimiter::tooManyAttempts($globalDownloadKey, $globalLimit)) {
            $this->logRateLimitExceeded('global', $globalDownloadKey, $request, $fileType);
            return $this->systemOverloadResponse($request);
        }
        
        // Increment counters
        RateLimiter::hit($userDownloadKey, 3600); // 1 hour
        RateLimiter::hit($rapidDownloadKey, 60);  // 1 minute
        RateLimiter::hit($globalDownloadKey, 3600); // 1 hour
        
        // Process the request
        $response = $next($request);
        
        // Track bandwidth usage after download
        if ($response->getStatusCode() === 200) {
            $this->trackBandwidthUsage($userBandwidthKey, $response, $fileType);
        }
        
        // Add rate limit headers
        $response->headers->add([
            'X-Download-Limit' => $downloadLimit,
            'X-Download-Remaining' => max(0, $downloadLimit - RateLimiter::attempts($userDownloadKey)),
            'X-Bandwidth-Limit-MB' => $bandwidthLimit,
            'X-Bandwidth-Used-MB' => round($currentBandwidth / (1024 * 1024), 2),
            'X-File-Type' => $fileType,
            'X-RateLimit-Reset' => now()->addSeconds(RateLimiter::availableIn($userDownloadKey))->timestamp
        ]);
        
        return $response;
    }
    
    /**
     * Detect file type from request
     */
    private function detectFileType(Request $request): string
    {
        $path = $request->path();
        $url = $request->url();
        
        // Extract file extension
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        // Map extensions to categories
        $typeMap = [
            'pdf' => 'document',
            'doc' => 'document',
            'docx' => 'document',
            'xls' => 'document',
            'xlsx' => 'document',
            'ppt' => 'document',
            'pptx' => 'document',
            'txt' => 'document',
            'rtf' => 'document',
            
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'bmp' => 'image',
            'svg' => 'image',
            'webp' => 'image',
            
            'mp4' => 'video',
            'avi' => 'video',
            'mov' => 'video',
            'wmv' => 'video',
            'flv' => 'video',
            'webm' => 'video',
            'mkv' => 'video',
            
            'mp3' => 'audio',
            'wav' => 'audio',
            'flac' => 'audio',
            'aac' => 'audio',
            'ogg' => 'audio',
            'wma' => 'audio',
            
            'zip' => 'archive',
            'rar' => 'archive',
            '7z' => 'archive',
            'tar' => 'archive',
            'gz' => 'archive',
            'bz2' => 'archive',
            
            'exe' => 'executable',
            'msi' => 'executable',
            'dmg' => 'executable',
            'deb' => 'executable',
            'rpm' => 'executable'
        ];
        
        return $typeMap[$extension] ?? 'other';
    }
    
    /**
     * Get download limit based on user role and file type
     */
    private function getDownloadLimit(string $userRole, string $fileType): int
    {
        $baseLimit = self::ROLE_DOWNLOAD_LIMITS[$userRole] ?? self::ROLE_DOWNLOAD_LIMITS['guest'];
        $multiplier = self::FILE_TYPE_MULTIPLIERS[$fileType] ?? 1.0;
        
        return max(1, intval($baseLimit / $multiplier));
    }
    
    /**
     * Get bandwidth limit based on user role and file type
     */
    private function getBandwidthLimit(string $userRole, string $fileType): int
    {
        $baseLimit = self::ROLE_BANDWIDTH_LIMITS[$userRole] ?? self::ROLE_BANDWIDTH_LIMITS['guest'];
        $multiplier = self::FILE_TYPE_MULTIPLIERS[$fileType] ?? 1.0;
        
        return max(10, intval($baseLimit / $multiplier)); // Minimum 10MB
    }
    
    /**
     * Track bandwidth usage
     */
    private function trackBandwidthUsage(string $key, $response, string $fileType)
    {
        $contentLength = $response->headers->get('Content-Length', 0);
        
        if ($contentLength > 0) {
            $currentUsage = Cache::get($key, 0);
            $newUsage = $currentUsage + $contentLength;
            Cache::put($key, $newUsage, 3600); // Store for 1 hour
            
            // Log large downloads
            if ($contentLength > 100 * 1024 * 1024) { // > 100MB
                Log::info('Large file download', [
                    'key' => $key,
                    'file_type' => $fileType,
                    'size_mb' => round($contentLength / (1024 * 1024), 2),
                    'user_id' => Auth::id(),
                    'ip' => request()->ip(),
                    'timestamp' => now()->toISOString()
                ]);
            }
        }
    }
    
    /**
     * Generate rate limit response
     */
    private function rateLimitResponse(Request $request, string $type, int $retryAfter)
    {
        $message = "Download {$type} limit exceeded. Please wait " . 
                  $this->formatRetryAfter($retryAfter) . " before downloading again.";
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Download Rate Limit Exceeded',
                'message' => $message,
                'code' => 'DOWNLOAD_RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
                'type' => $type,
                'timestamp' => now()->toISOString()
            ], 429);
        }
        
        return response($message, 429)
            ->header('Retry-After', $retryAfter)
            ->header('Content-Type', 'text/plain');
    }
    
    /**
     * Generate bandwidth limit response
     */
    private function bandwidthLimitResponse(Request $request, int $limitMB)
    {
        $message = "Bandwidth limit of {$limitMB}MB per hour exceeded. Please wait before downloading more files.";
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Bandwidth Limit Exceeded',
                'message' => $message,
                'code' => 'BANDWIDTH_LIMIT_EXCEEDED',
                'limit_mb' => $limitMB,
                'retry_after' => 3600,
                'timestamp' => now()->toISOString()
            ], 429);
        }
        
        return response($message, 429)
            ->header('Retry-After', 3600)
            ->header('Content-Type', 'text/plain');
    }
    
    /**
     * Generate suspicious activity response
     */
    private function suspiciousActivityResponse(Request $request)
    {
        $message = "Suspicious download activity detected. Access temporarily restricted.";
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Suspicious Activity',
                'message' => $message,
                'code' => 'SUSPICIOUS_DOWNLOAD_ACTIVITY',
                'retry_after' => 600,
                'timestamp' => now()->toISOString()
            ], 429);
        }
        
        return response($message, 429)
            ->header('Retry-After', 600)
            ->header('Content-Type', 'text/plain');
    }
    
    /**
     * Generate system overload response
     */
    private function systemOverloadResponse(Request $request)
    {
        $message = "System is currently experiencing high download volume. Please try again later.";
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'System Overload',
                'message' => $message,
                'code' => 'SYSTEM_DOWNLOAD_OVERLOAD',
                'retry_after' => 1800,
                'timestamp' => now()->toISOString()
            ], 503);
        }
        
        return response($message, 503)
            ->header('Retry-After', 1800)
            ->header('Content-Type', 'text/plain');
    }
    
    /**
     * Log rate limit exceeded events
     */
    private function logRateLimitExceeded(string $type, string $key, Request $request, string $fileType)
    {
        Log::warning('Download rate limit exceeded', [
            'limit_type' => $type,
            'key' => $key,
            'file_type' => $fileType,
            'ip' => $request->ip(),
            'user_id' => Auth::id(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(Request $request, string $fileType)
    {
        Log::alert('Suspicious download activity detected', [
            'file_type' => $fileType,
            'ip' => $request->ip(),
            'user_id' => Auth::id(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'timestamp' => now()->toISOString(),
            'severity' => 'high'
        ]);
    }
    
    /**
     * Format retry after time in human readable format
     */
    private function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            return ceil($seconds / 60) . ' minutes';
        } else {
            return ceil($seconds / 3600) . ' hours';
        }
    }
}