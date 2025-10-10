<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductionSecurityMiddleware
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
        // Force HTTPS in production
        if (app()->environment('production') && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        // Log security-relevant requests
        $this->logSecurityEvents($request);

        $response = $next($request);

        // Add security headers
        $this->addSecurityHeaders($response);

        return $response;
    }

    /**
     * Add security headers to the response
     */
    private function addSecurityHeaders($response)
    {
        if (app()->environment('production')) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
            
            // HSTS header for HTTPS
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            
            // Content Security Policy
            $csp = "default-src 'self'; " .
                   "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
                   "style-src 'self' 'unsafe-inline'; " .
                   "img-src 'self' data: https:; " .
                   "font-src 'self'; " .
                   "connect-src 'self'; " .
                   "frame-ancestors 'none';";
            
            $response->headers->set('Content-Security-Policy', $csp);
        }
    }

    /**
     * Log security-relevant events
     */
    private function logSecurityEvents(Request $request)
    {
        // Log failed login attempts
        if ($request->is('login') && $request->isMethod('POST')) {
            Log::channel('security')->info('Login attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);
        }

        // Log file upload attempts
        if ($request->hasFile('*')) {
            Log::channel('file_uploads')->info('File upload attempt', [
                'ip' => $request->ip(),
                'route' => $request->route()?->getName(),
                'files_count' => count($request->allFiles()),
                'timestamp' => now(),
            ]);
        }

        // Log suspicious requests
        $suspiciousPatterns = [
            'sql injection' => '/(\bunion\b|\bselect\b|\binsert\b|\bdelete\b|\bdrop\b)/i',
            'xss attempt' => '/<script|javascript:|on\w+\s*=/i',
            'path traversal' => '/\.\.[\/\\\\]/i',
        ];

        foreach ($suspiciousPatterns as $type => $pattern) {
            $requestData = json_encode($request->all());
            if (preg_match($pattern, $requestData)) {
                Log::channel('security')->warning("Suspicious request detected: {$type}", [
                    'ip' => $request->ip(),
                    'route' => $request->route()?->getName(),
                    'user_agent' => $request->userAgent(),
                    'request_data' => $requestData,
                    'timestamp' => now(),
                ]);
            }
        }
    }
}