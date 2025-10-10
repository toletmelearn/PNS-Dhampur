<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SecurityValidationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Perform security validations
        $this->validateRequestSize($request);
        $this->validateSqlInjection($request);
        $this->validateXssAttempts($request);
        $this->validateFileUploadSecurity($request);
        $this->validateRateLimiting($request);
        $this->validateSuspiciousPatterns($request);
        $this->validateHeaderSecurity($request);

        $response = $next($request);

        // Add security headers to response
        $this->addSecurityHeaders($response);

        return $response;
    }

    /**
     * Validate request size to prevent DoS attacks.
     */
    protected function validateRequestSize(Request $request): void
    {
        $maxSize = config('security.max_request_size', 10 * 1024 * 1024); // 10MB default
        
        if ($request->server('CONTENT_LENGTH') > $maxSize) {
            Log::warning('Request size exceeded limit', [
                'ip' => $request->ip(),
                'size' => $request->server('CONTENT_LENGTH'),
                'max_size' => $maxSize,
                'url' => $request->fullUrl(),
            ]);
            
            abort(413, 'Request entity too large');
        }
    }

    /**
     * Validate and prevent SQL injection attempts.
     */
    protected function validateSqlInjection(Request $request): void
    {
        $suspiciousPatterns = [
            // SQL injection patterns
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
            '/(\bINSERT\b.*\bINTO\b.*\bVALUES\b)/i',
            '/(\bUPDATE\b.*\bSET\b.*\bWHERE\b)/i',
            '/(\bDELETE\b.*\bFROM\b.*\bWHERE\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bALTER\b.*\bTABLE\b)/i',
            '/(\bCREATE\b.*\bTABLE\b)/i',
            '/(\'|\")(\s*;\s*|\s*--|\s*\/\*)/i',
            '/(\bOR\b\s+\d+\s*=\s*\d+|\bAND\b\s+\d+\s*=\s*\d+)/i',
            '/(\bxp_cmdshell\b|\bsp_executesql\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)(\s+|\()/i',
        ];

        $allInput = $request->all();
        $this->checkPatternsInData($allInput, $suspiciousPatterns, 'SQL injection attempt detected', $request);
    }

    /**
     * Validate and prevent XSS attempts.
     */
    protected function validateXssAttempts(Request $request): void
    {
        $xssPatterns = [
            // Script tags
            '/<script[^>]*>.*?<\/script>/is',
            '/<script[^>]*>/i',
            // Event handlers
            '/on\w+\s*=\s*["\']?[^"\']*["\']?/i',
            // JavaScript URLs
            '/javascript\s*:/i',
            // Data URLs with scripts
            '/data\s*:\s*text\/html/i',
            // Common XSS vectors
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i',
            '/<link[^>]*>/i',
            '/<meta[^>]*>/i',
            // Expression and eval
            '/expression\s*\(/i',
            '/eval\s*\(/i',
            // Vbscript
            '/vbscript\s*:/i',
        ];

        $allInput = $request->all();
        $this->checkPatternsInData($allInput, $xssPatterns, 'XSS attempt detected', $request);
    }

    /**
     * Validate file upload security.
     */
    protected function validateFileUploadSecurity(Request $request): void
    {
        if (!$request->hasFile('*')) {
            return;
        }

        foreach ($request->allFiles() as $key => $files) {
            $files = is_array($files) ? $files : [$files];
            
            foreach ($files as $file) {
                if (!$file->isValid()) {
                    continue;
                }

                // Check file size
                $maxSize = config('security.max_file_size', 10 * 1024 * 1024); // 10MB
                if ($file->getSize() > $maxSize) {
                    Log::warning('File upload size exceeded', [
                        'ip' => $request->ip(),
                        'filename' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                    ]);
                    abort(413, 'File too large');
                }

                // Check dangerous file extensions
                $dangerousExtensions = [
                    'php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp',
                    'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar',
                    'sh', 'py', 'pl', 'rb', 'ps1'
                ];

                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, $dangerousExtensions)) {
                    Log::warning('Dangerous file upload attempt', [
                        'ip' => $request->ip(),
                        'filename' => $file->getClientOriginalName(),
                        'extension' => $extension,
                    ]);
                    abort(400, 'File type not allowed');
                }

                // Check MIME type
                $allowedMimes = config('security.allowed_mime_types', [
                    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                    'application/pdf', 'text/plain',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ]);

                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    Log::warning('Invalid MIME type upload attempt', [
                        'ip' => $request->ip(),
                        'filename' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                    abort(400, 'File type not supported');
                }

                // Check for embedded scripts in files
                $this->scanFileContent($file, $request);
            }
        }
    }

    /**
     * Validate rate limiting.
     */
    protected function validateRateLimiting(Request $request): void
    {
        $ip = $request->ip();
        $key = 'security_rate_limit:' . $ip;
        
        // General rate limiting
        if (RateLimiter::tooManyAttempts($key, 100)) { // 100 requests per minute
            Log::warning('Rate limit exceeded', [
                'ip' => $ip,
                'url' => $request->fullUrl(),
            ]);
            abort(429, 'Too many requests');
        }

        RateLimiter::hit($key, 60); // 1 minute window
    }

    /**
     * Validate suspicious patterns and behaviors.
     */
    protected function validateSuspiciousPatterns(Request $request): void
    {
        $suspiciousPatterns = [
            // Path traversal
            '/\.\.[\/\\\\]/i',
            '/\.\.[%2f%5c]/i',
            // Command injection
            '/[;&|`$(){}[\]]/i',
            // LDAP injection
            '/[()=*!&|]/i',
            // XML injection
            '/<\?xml/i',
            '/<!DOCTYPE/i',
            '/<!ENTITY/i',
        ];

        $url = $request->fullUrl();
        $userAgent = $request->userAgent();

        // Check URL for suspicious patterns
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                Log::warning('Suspicious URL pattern detected', [
                    'ip' => $request->ip(),
                    'url' => $url,
                    'pattern' => $pattern,
                    'user_agent' => $userAgent,
                ]);
                abort(400, 'Invalid request');
            }
        }

        // Check for bot-like behavior
        if ($this->isSuspiciousUserAgent($userAgent)) {
            Log::info('Suspicious user agent detected', [
                'ip' => $request->ip(),
                'user_agent' => $userAgent,
                'url' => $url,
            ]);
        }
    }

    /**
     * Validate security headers.
     */
    protected function validateHeaderSecurity(Request $request): void
    {
        // Check for suspicious headers
        $suspiciousHeaders = [
            'X-Forwarded-For' => '/[<>"\']/',
            'X-Real-IP' => '/[<>"\']/',
            'User-Agent' => '/[<>]/',
            'Referer' => '/javascript:|data:|vbscript:/',
        ];

        foreach ($suspiciousHeaders as $header => $pattern) {
            $value = $request->header($header);
            if ($value && preg_match($pattern, $value)) {
                Log::warning('Suspicious header detected', [
                    'ip' => $request->ip(),
                    'header' => $header,
                    'value' => $value,
                ]);
                abort(400, 'Invalid request headers');
            }
        }
    }

    /**
     * Check patterns in data recursively.
     */
    protected function checkPatternsInData(array $data, array $patterns, string $logMessage, Request $request): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->checkPatternsInData($value, $patterns, $logMessage, $request);
            } elseif (is_string($value)) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        Log::warning($logMessage, [
                            'ip' => $request->ip(),
                            'field' => $key,
                            'value' => substr($value, 0, 100) . '...',
                            'pattern' => $pattern,
                            'url' => $request->fullUrl(),
                            'user_agent' => $request->userAgent(),
                        ]);
                        abort(400, 'Invalid input detected');
                    }
                }
            }
        }
    }

    /**
     * Scan file content for malicious patterns.
     */
    protected function scanFileContent($file, Request $request): void
    {
        $content = file_get_contents($file->getPathname());
        
        $maliciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/shell_exec\s*\(/i',
            '/passthru\s*\(/i',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                Log::warning('Malicious file content detected', [
                    'ip' => $request->ip(),
                    'filename' => $file->getClientOriginalName(),
                    'pattern' => $pattern,
                ]);
                abort(400, 'File contains malicious content');
            }
        }
    }

    /**
     * Check if user agent is suspicious.
     */
    protected function isSuspiciousUserAgent(?string $userAgent): bool
    {
        if (!$userAgent) {
            return true;
        }

        $suspiciousAgents = [
            'curl', 'wget', 'python', 'perl', 'ruby', 'java',
            'bot', 'crawler', 'spider', 'scraper', 'scanner'
        ];

        $userAgentLower = strtolower($userAgent);
        
        foreach ($suspiciousAgents as $agent) {
            if (strpos($userAgentLower, $agent) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add security headers to response.
     */
    protected function addSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        
        // Only add HSTS for HTTPS
        if (request()->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}