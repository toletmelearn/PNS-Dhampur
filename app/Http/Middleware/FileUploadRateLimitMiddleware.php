<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FileUploadRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('fileupload.rate_limiting.enable_upload_rate_limit', true)) {
            return $next($request);
        }

        $userId = auth()->id() ?? $request->ip();
        $now = now();
        
        // Check if request has file uploads
        if (!$this->hasFileUploads($request)) {
            return $next($request);
        }

        // Rate limiting checks
        if (!$this->checkUploadLimits($userId, $request)) {
            Log::warning('File upload rate limit exceeded', [
                'user_id' => $userId,
                'ip' => $request->ip(),
                'route' => $request->route()->getName(),
                'timestamp' => $now
            ]);

            return response()->json([
                'error' => 'Upload rate limit exceeded. Please try again later.',
                'retry_after' => 60
            ], 429);
        }

        $response = $next($request);

        // Track successful uploads
        if ($response->getStatusCode() < 400) {
            $this->trackUpload($userId, $request);
        }

        return $response;
    }

    /**
     * Check if request has file uploads
     *
     * @param Request $request
     * @return bool
     */
    private function hasFileUploads(Request $request): bool
    {
        foreach ($request->allFiles() as $file) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    if ($f && $f->isValid()) {
                        return true;
                    }
                }
            } elseif ($file && $file->isValid()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check upload limits
     *
     * @param string $userId
     * @param Request $request
     * @return bool
     */
    private function checkUploadLimits(string $userId, Request $request): bool
    {
        $maxPerMinute = config('fileupload.rate_limiting.max_uploads_per_minute', 10);
        $maxPerHour = config('fileupload.rate_limiting.max_uploads_per_hour', 100);
        $maxSizePerHour = config('fileupload.rate_limiting.max_total_size_per_hour', 500 * 1024 * 1024);

        // Check uploads per minute
        $minuteKey = "upload_count_minute:{$userId}:" . now()->format('Y-m-d-H-i');
        $minuteCount = Cache::get($minuteKey, 0);
        if ($minuteCount >= $maxPerMinute) {
            return false;
        }

        // Check uploads per hour
        $hourKey = "upload_count_hour:{$userId}:" . now()->format('Y-m-d-H');
        $hourCount = Cache::get($hourKey, 0);
        if ($hourCount >= $maxPerHour) {
            return false;
        }

        // Check total size per hour
        $sizeKey = "upload_size_hour:{$userId}:" . now()->format('Y-m-d-H');
        $totalSize = Cache::get($sizeKey, 0);
        $requestSize = $this->calculateRequestSize($request);
        
        if (($totalSize + $requestSize) > $maxSizePerHour) {
            return false;
        }

        return true;
    }

    /**
     * Track upload for rate limiting
     *
     * @param string $userId
     * @param Request $request
     * @return void
     */
    private function trackUpload(string $userId, Request $request): void
    {
        $now = now();
        
        // Track minute count
        $minuteKey = "upload_count_minute:{$userId}:" . $now->format('Y-m-d-H-i');
        Cache::increment($minuteKey);
        Cache::put($minuteKey, Cache::get($minuteKey), 60);

        // Track hour count
        $hourKey = "upload_count_hour:{$userId}:" . $now->format('Y-m-d-H');
        Cache::increment($hourKey);
        Cache::put($hourKey, Cache::get($hourKey), 3600);

        // Track hour size
        $sizeKey = "upload_size_hour:{$userId}:" . $now->format('Y-m-d-H');
        $requestSize = $this->calculateRequestSize($request);
        $currentSize = Cache::get($sizeKey, 0);
        Cache::put($sizeKey, $currentSize + $requestSize, 3600);
    }

    /**
     * Calculate total size of uploaded files in request
     *
     * @param Request $request
     * @return int
     */
    private function calculateRequestSize(Request $request): int
    {
        $totalSize = 0;
        
        foreach ($request->allFiles() as $file) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    if ($f && $f->isValid()) {
                        $totalSize += $f->getSize();
                    }
                }
            } elseif ($file && $file->isValid()) {
                $totalSize += $file->getSize();
            }
        }
        
        return $totalSize;
    }
}