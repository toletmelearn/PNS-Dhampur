<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class EnhancedSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Rate limiting for API endpoints
        if ($this->isApiRoute($request)) {
            $key = 'api:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 100)) { // 100 requests per minute
                Log::warning('API rate limit exceeded', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'path' => $request->path()
                ]);
                return response()->json([
                    'error' => 'Too many requests',
                    'retry_after' => RateLimiter::availableIn($key)
                ], 429);
            }
            RateLimiter::hit($key);
        }

        // Security headers
        $response = $next($request);
        
        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Remove sensitive headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    private function isApiRoute(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }
}