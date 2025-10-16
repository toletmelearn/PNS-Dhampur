<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    /**
     * Suspicious patterns to detect in requests
     */
    private array $suspiciousPatterns = [
        // SQL Injection patterns
        '/(\bunion\b.*\bselect\b)|(\bselect\b.*\bunion\b)/i',
        '/(\bdrop\b.*\btable\b)|(\btable\b.*\bdrop\b)/i',
        '/(\binsert\b.*\binto\b)|(\binto\b.*\binsert\b)/i',
        '/(\bdelete\b.*\bfrom\b)|(\bfrom\b.*\bdelete\b)/i',
        '/(\bupdate\b.*\bset\b)|(\bset\b.*\bupdate\b)/i',
        
        // XSS patterns
        '/<script[^>]*>.*?<\/script>/i',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe[^>]*>.*?<\/iframe>/i',
        '/<object[^>]*>.*?<\/object>/i',
        
        // Path traversal
        '/\.\.\//i',
        '/\.\.\\/i',
        '/\.\.%2f/i',
        '/\.\.%5c/i',
        
        // Command injection
        '/;\s*(rm|del|format|shutdown|reboot)/i',
        '/\|\s*(rm|del|format|shutdown|reboot)/i',
        '/&&\s*(rm|del|format|shutdown|reboot)/i',
        
        // File inclusion
        '/php:\/\//i',
        '/file:\/\//i',
        '/data:\/\//i',
        '/expect:\/\//i'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Security headers
        $this->addSecurityHeaders($request);
        
        // Rate limiting
        if ($this->isRateLimited($request)) {
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json(['error' => 'Too many requests'], 429);
        }

        // Validate request size
        if ($this->isRequestTooLarge($request)) {
            Log::warning('Request size too large', [
                'ip' => $request->ip(),
                'content_length' => $request->header('Content-Length'),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json(['error' => 'Request too large'], 413);
        }

        // Check for suspicious patterns
        if ($this->hasSuspiciousContent($request)) {
            Log::alert('Suspicious request detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => Auth::id(),
                'input' => $this->sanitizeLogData($request->all())
            ]);
            
            // Block the request
            return response()->json(['error' => 'Request blocked for security reasons'], 403);
        }

        // Validate file uploads
        if ($request->hasFile('*') && !$this->validateFileUploads($request)) {
            Log::warning('Invalid file upload attempt', [
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
                'files' => array_keys($request->allFiles())
            ]);
            
            return response()->json(['error' => 'Invalid file upload'], 400);
        }

        // Check for bot/crawler patterns
        if ($this->isSuspiciousBot($request)) {
            Log::info('Suspicious bot detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);
            
            return response()->json(['error' => 'Access denied'], 403);
        }

        // Validate referrer for sensitive operations
        if ($this->requiresReferrerValidation($request) && !$this->isValidReferrer($request)) {
            Log::warning('Invalid referrer for sensitive operation', [
                'ip' => $request->ip(),
                'referrer' => $request->header('Referer'),
                'url' => $request->fullUrl(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json(['error' => 'Invalid request origin'], 403);
        }

        // Add security context to request
        $request->merge([
            'security_context' => [
                'ip' => $request->ip(),
                'user_agent_hash' => hash('sha256', $request->userAgent()),
                'timestamp' => now()->timestamp,
                'session_id' => session()->getId()
            ]
        ]);

        $response = $next($request);

        // Add additional security headers to response
        $this->addResponseSecurityHeaders($response);

        // Log successful request
        $this->logSecureRequest($request, $response);

        return $response;
    }

    /**
     * Add security headers to the request
     */
    private function addSecurityHeaders(Request $request): void
    {
        // Set secure headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        if ($request->secure()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    /**
     * Check if request is rate limited
     */
    private function isRateLimited(Request $request): bool
    {
        $key = 'security_rate_limit:' . $request->ip();
        
        // Different limits for different types of requests
        $limit = $this->getRateLimit($request);
        $decay = 60; // 1 minute window
        
        return RateLimiter::tooManyAttempts($key, $limit);
    }

    /**
     * Get rate limit based on request type
     */
    private function getRateLimit(Request $request): int
    {
        // Authenticated users get higher limits
        if (Auth::check()) {
            return 300; // 300 requests per minute for authenticated users
        }
        
        // API endpoints get different limits
        if ($request->is('api/*')) {
            return 100; // 100 requests per minute for API
        }
        
        // Login/register endpoints get stricter limits
        if ($request->is('login') || $request->is('register') || $request->is('password/*')) {
            return 10; // 10 requests per minute for auth endpoints
        }
        
        return 60; // Default 60 requests per minute
    }

    /**
     * Check if request size is too large
     */
    private function isRequestTooLarge(Request $request): bool
    {
        $maxSize = config('security.max_request_size', 10 * 1024 * 1024); // 10MB default
        $contentLength = $request->header('Content-Length', 0);
        
        return $contentLength > $maxSize;
    }

    /**
     * Check for suspicious content in request
     */
    private function hasSuspiciousContent(Request $request): bool
    {
        $content = json_encode($request->all()) . $request->getUri();
        
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate file uploads
     */
    private function validateFileUploads(Request $request): bool
    {
        $allowedMimes = config('security.allowed_file_types', [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'text/csv'
        ]);
        
        $maxFileSize = config('security.max_file_size', 5 * 1024 * 1024); // 5MB default
        
        foreach ($request->allFiles() as $files) {
            $files = is_array($files) ? $files : [$files];
            
            foreach ($files as $file) {
                // Check file size
                if ($file->getSize() > $maxFileSize) {
                    return false;
                }
                
                // Check MIME type
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    return false;
                }
                
                // Check for executable files
                $extension = strtolower($file->getClientOriginalExtension());
                $dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js'];
                
                if (in_array($extension, $dangerousExtensions)) {
                    return false;
                }
                
                // Additional security checks for images
                if (Str::startsWith($file->getMimeType(), 'image/')) {
                    if (!$this->isValidImage($file)) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * Validate image files
     */
    private function isValidImage($file): bool
    {
        try {
            $imageInfo = getimagesize($file->getPathname());
            return $imageInfo !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if user agent indicates suspicious bot
     */
    private function isSuspiciousBot(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent());
        
        $suspiciousBots = [
            'sqlmap', 'nikto', 'nmap', 'masscan', 'zap', 'burp',
            'havij', 'acunetix', 'netsparker', 'appscan', 'w3af'
        ];
        
        foreach ($suspiciousBots as $bot) {
            if (Str::contains($userAgent, $bot)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if request requires referrer validation
     */
    private function requiresReferrerValidation(Request $request): bool
    {
        // Require referrer validation for state-changing operations
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']) &&
               !$request->is('api/*'); // API requests use token authentication
    }

    /**
     * Validate referrer header
     */
    private function isValidReferrer(Request $request): bool
    {
        $referrer = $request->header('Referer');
        
        if (!$referrer) {
            return false;
        }
        
        $allowedDomains = [
            $request->getHost(),
            config('app.url'),
            // Add other allowed domains
        ];
        
        $referrerHost = parse_url($referrer, PHP_URL_HOST);
        
        return in_array($referrerHost, $allowedDomains);
    }

    /**
     * Add security headers to response
     */
    private function addResponseSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; " .
               "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self'; " .
               "frame-ancestors 'none';";
        
        $response->headers->set('Content-Security-Policy', $csp);
    }

    /**
     * Log secure request
     */
    private function logSecureRequest(Request $request, Response $response): void
    {
        // Only log if there are security concerns or errors
        if ($response->getStatusCode() >= 400 || Auth::check()) {
            Log::info('Security middleware processed request', [
                'ip' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status_code' => $response->getStatusCode(),
                'user_id' => Auth::id(),
                'user_agent_hash' => hash('sha256', $request->userAgent())
            ]);
        }
    }

    /**
     * Sanitize data for logging
     */
    private function sanitizeLogData(array $data): array
    {
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_key', 'secret'];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeLogData($value);
            }
        }
        
        return $data;
    }
}