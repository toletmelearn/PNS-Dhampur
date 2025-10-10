<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\CacheOptimizationService;

class CacheMiddleware
{
    protected $cacheService;
    
    /**
     * Cache durations for different types of content (in minutes)
     */
    protected $cacheDurations = [
        'dashboard' => 5,      // 5 minutes for dashboard data
        'reports' => 30,       // 30 minutes for reports
        'static' => 1440,      // 24 hours for static data
        'user_data' => 15,     // 15 minutes for user-specific data
        'system' => 60,        // 1 hour for system data
    ];

    /**
     * Routes that should be cached
     */
    protected $cacheableRoutes = [
        'api/student/dashboard' => 'dashboard',
        'api/teacher/dashboard' => 'dashboard',
        'api/admin/dashboard' => 'dashboard',
        'api/reports/academic' => 'reports',
        'api/reports/financial' => 'reports',
        'api/reports/attendance' => 'reports',
        'api/reports/performance' => 'reports',
        'api/classes' => 'static',
        'api/subjects' => 'static',
        'api/student/assignments' => 'user_data',
        'api/student/attendance' => 'user_data',
        'api/teacher/classes' => 'user_data',
        'api/system/settings' => 'system',
    ];

    public function __construct(CacheOptimizationService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Only cache GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Check if this route should be cached
        $cacheType = $this->getCacheType($request);
        if (!$cacheType) {
            return $next($request);
        }

        // Generate cache key
        $cacheKey = $this->generateCacheKey($request);
        
        // Try to get from cache
        $cachedResponse = Cache::get($cacheKey);
        if ($cachedResponse && $this->isValidCachedResponse($cachedResponse)) {
            Log::info('Cache hit', [
                'key' => $cacheKey,
                'route' => $request->path(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json($cachedResponse['data'])
                ->header('X-Cache-Status', 'HIT')
                ->header('X-Cache-Key', $cacheKey)
                ->header('X-Cache-Expires', $cachedResponse['expires_at']);
        }

        // Process request
        $response = $next($request);

        // Cache successful JSON responses
        if ($response->isSuccessful() && $this->isJsonResponse($response)) {
            $this->cacheResponse($cacheKey, $response, $cacheType);
            
            Log::info('Response cached', [
                'key' => $cacheKey,
                'route' => $request->path(),
                'user_id' => Auth::id(),
                'duration' => $this->cacheDurations[$cacheType]
            ]);
        }

        return $response->header('X-Cache-Status', 'MISS')
                       ->header('X-Cache-Key', $cacheKey);
    }

    /**
     * Determine cache type for the request
     */
    protected function getCacheType(Request $request): ?string
    {
        $path = $request->path();
        
        foreach ($this->cacheableRoutes as $route => $type) {
            if (str_starts_with($path, $route)) {
                return $type;
            }
        }
        
        return null;
    }

    /**
     * Generate cache key for the request
     */
    protected function generateCacheKey(Request $request): string
    {
        $components = [
            'route' => $request->path(),
            'method' => $request->method(),
            'user_id' => Auth::id(),
            'user_role' => Auth::user()?->role,
            'query' => $request->query(),
        ];

        // Add user-specific components for personalized data
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'student') {
                $components['student_id'] = $user->student?->id;
                $components['class_id'] = $user->student?->class_id;
            } elseif ($user->role === 'teacher' || $user->role === 'class_teacher') {
                $components['teacher_id'] = $user->teacher?->id;
            }
        }

        return 'api_cache:' . md5(serialize($components));
    }

    /**
     * Cache the response
     */
    protected function cacheResponse(string $cacheKey, $response, string $cacheType): void
    {
        $duration = $this->cacheDurations[$cacheType];
        $expiresAt = now()->addMinutes($duration);
        
        $cacheData = [
            'data' => json_decode($response->getContent(), true),
            'headers' => $response->headers->all(),
            'status_code' => $response->getStatusCode(),
            'cached_at' => now()->toISOString(),
            'expires_at' => $expiresAt->toISOString(),
            'cache_type' => $cacheType,
        ];

        Cache::put($cacheKey, $cacheData, $duration * 60); // Convert to seconds
    }

    /**
     * Check if cached response is valid
     */
    protected function isValidCachedResponse($cachedResponse): bool
    {
        if (!is_array($cachedResponse) || !isset($cachedResponse['expires_at'])) {
            return false;
        }

        return now()->lt($cachedResponse['expires_at']);
    }

    /**
     * Check if response is JSON
     */
    protected function isJsonResponse($response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        return str_contains($contentType, 'application/json') || 
               (is_array(json_decode($response->getContent(), true)) && json_last_error() === JSON_ERROR_NONE);
    }

    /**
     * Clear cache for specific patterns
     */
    public static function clearCacheByPattern(string $pattern): int
    {
        $keys = Cache::getRedis()->keys("api_cache:*{$pattern}*");
        $cleared = 0;
        
        foreach ($keys as $key) {
            if (Cache::forget($key)) {
                $cleared++;
            }
        }
        
        Log::info('Cache cleared by pattern', [
            'pattern' => $pattern,
            'keys_cleared' => $cleared
        ]);
        
        return $cleared;
    }

    /**
     * Clear cache for specific user
     */
    public static function clearUserCache(int $userId): int
    {
        return self::clearCacheByPattern("user_id\";i:{$userId}");
    }

    /**
     * Clear cache for specific route
     */
    public static function clearRouteCache(string $route): int
    {
        return self::clearCacheByPattern("route\";s:" . strlen($route) . ":\"{$route}\"");
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        $redis = Cache::getRedis();
        $keys = $redis->keys('api_cache:*');
        
        $stats = [
            'total_keys' => count($keys),
            'by_type' => [],
            'by_route' => [],
            'total_size' => 0,
        ];
        
        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data && is_array($data)) {
                $type = $data['cache_type'] ?? 'unknown';
                $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
                
                // Estimate size
                $stats['total_size'] += strlen(serialize($data));
            }
        }
        
        return $stats;
    }
}