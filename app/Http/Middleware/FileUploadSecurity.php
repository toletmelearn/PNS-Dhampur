<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class FileUploadSecurity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to requests with file uploads
        if (!$request->hasFile() && !$this->hasFileUploadFields($request)) {
            return $next($request);
        }

        // Check if IP is blocked
        if ($this->isIpBlocked($request->ip())) {
            Log::warning('Blocked IP attempted file upload', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'error' => 'Access denied. Your IP has been temporarily blocked.'
            ], 403);
        }

        // Apply rate limiting
        if ($this->isRateLimited($request)) {
            $this->logSuspiciousActivity($request, 'rate_limit_exceeded');
            
            return response()->json([
                'error' => 'Too many upload attempts. Please try again later.'
            ], 429);
        }

        // Validate request structure
        if (!$this->validateRequestStructure($request)) {
            $this->logSuspiciousActivity($request, 'invalid_request_structure');
            
            return response()->json([
                'error' => 'Invalid request format.'
            ], 400);
        }

        // Check for suspicious patterns
        if ($this->containsSuspiciousPatterns($request)) {
            $this->logSuspiciousActivity($request, 'suspicious_patterns');
            $this->blockIpTemporarily($request->ip());
            
            return response()->json([
                'error' => 'Request blocked due to security concerns.'
            ], 403);
        }

        // Validate file upload limits
        if (!$this->validateUploadLimits($request)) {
            return response()->json([
                'error' => 'Upload limits exceeded.'
            ], 413);
        }

        // Add security headers to response
        $response = $next($request);
        
        return $this->addSecurityHeaders($response);
    }

    /**
     * Check if request has file upload fields.
     */
    protected function hasFileUploadFields(Request $request): bool
    {
        // Check for common file upload field names
        $fileFields = [
            'file', 'files', 'upload', 'uploads', 'document', 'documents',
            'image', 'images', 'photo', 'photos', 'avatar', 'profile_photo',
            'attachment', 'attachments', 'certificate', 'certificates'
        ];

        foreach ($fileFields as $field) {
            if ($request->has($field) || $request->hasFile($field)) {
                return true;
            }
        }

        // Check for array file fields
        foreach ($request->all() as $key => $value) {
            if (is_array($value) && !empty($value)) {
                foreach ($value as $item) {
                    if ($item instanceof \Illuminate\Http\UploadedFile) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is blocked.
     */
    protected function isIpBlocked(string $ip): bool
    {
        return Cache::has("blocked_ip:{$ip}");
    }

    /**
     * Check if request is rate limited.
     */
    protected function isRateLimited(Request $request): bool
    {
        $key = 'file_upload:' . $request->ip();
        
        // Allow 10 uploads per minute per IP
        return RateLimiter::tooManyAttempts($key, 10);
    }

    /**
     * Validate request structure.
     */
    protected function validateRequestStructure(Request $request): bool
    {
        // Check Content-Type header for multipart/form-data
        $contentType = $request->header('Content-Type', '');
        
        if ($request->hasFile() && !str_contains($contentType, 'multipart/form-data')) {
            return false;
        }

        // Check for required headers
        $requiredHeaders = ['User-Agent', 'Accept'];
        foreach ($requiredHeaders as $header) {
            if (!$request->hasHeader($header)) {
                return false;
            }
        }

        // Validate Content-Length if present
        $contentLength = $request->header('Content-Length');
        if ($contentLength !== null) {
            $actualSize = strlen($request->getContent());
            if (abs($actualSize - (int)$contentLength) > 1024) { // Allow 1KB tolerance
                return false;
            }
        }

        return true;
    }

    /**
     * Check for suspicious patterns in request.
     */
    protected function containsSuspiciousPatterns(Request $request): bool
    {
        // Check User-Agent for suspicious patterns
        $userAgent = $request->userAgent();
        $suspiciousUserAgents = [
            'curl', 'wget', 'python-requests', 'bot', 'crawler', 'scanner',
            'nikto', 'sqlmap', 'nmap', 'masscan', 'zap', 'burp'
        ];

        foreach ($suspiciousUserAgents as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        // Check for suspicious referers
        $referer = $request->header('Referer', '');
        if (!empty($referer) && !$this->isValidReferer($referer)) {
            return true;
        }

        // Check for suspicious request parameters
        $suspiciousParams = [
            '../', '..\\', '/etc/', '/var/', '/usr/', '/bin/', '/sbin/',
            'cmd=', 'exec=', 'system=', 'shell=', 'eval=', 'base64_decode',
            '<script', 'javascript:', 'vbscript:', 'onload=', 'onerror=',
            'UNION SELECT', 'DROP TABLE', 'INSERT INTO', 'DELETE FROM'
        ];

        $allInput = json_encode($request->all());
        foreach ($suspiciousParams as $pattern) {
            if (stripos($allInput, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate if referer is from allowed domains.
     */
    protected function isValidReferer(string $referer): bool
    {
        $allowedDomains = [
            $request->getHost(),
            'localhost',
            '127.0.0.1',
            config('app.url')
        ];

        $refererHost = parse_url($referer, PHP_URL_HOST);
        
        return in_array($refererHost, $allowedDomains);
    }

    /**
     * Validate upload limits.
     */
    protected function validateUploadLimits(Request $request): bool
    {
        $maxFiles = config('app.max_upload_files', 10);
        $maxTotalSize = config('app.max_total_upload_size', 52428800); // 50MB

        $fileCount = 0;
        $totalSize = 0;

        // Count files and calculate total size
        foreach ($request->allFiles() as $files) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $fileCount++;
                        $totalSize += $file->getSize();
                    }
                }
            } else {
                if ($files instanceof \Illuminate\Http\UploadedFile) {
                    $fileCount++;
                    $totalSize += $files->getSize();
                }
            }
        }

        // Check limits
        if ($fileCount > $maxFiles) {
            Log::warning('Too many files uploaded', [
                'ip' => $request->ip(),
                'file_count' => $fileCount,
                'max_allowed' => $maxFiles,
            ]);
            return false;
        }

        if ($totalSize > $maxTotalSize) {
            Log::warning('Upload size limit exceeded', [
                'ip' => $request->ip(),
                'total_size' => $totalSize,
                'max_allowed' => $maxTotalSize,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Log suspicious activity.
     */
    protected function logSuspiciousActivity(Request $request, string $reason): void
    {
        Log::warning('Suspicious file upload activity', [
            'reason' => $reason,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ]);

        // Increment suspicious activity counter
        $key = "suspicious_activity:{$request->ip()}";
        $count = Cache::increment($key, 1);
        Cache::put($key, $count, now()->addHours(24));

        // Block IP if too many suspicious activities
        if ($count >= 5) {
            $this->blockIpTemporarily($request->ip(), 60); // Block for 1 hour
        }
    }

    /**
     * Block IP temporarily.
     */
    protected function blockIpTemporarily(string $ip, int $minutes = 30): void
    {
        Cache::put("blocked_ip:{$ip}", true, now()->addMinutes($minutes));
        
        Log::warning('IP blocked temporarily', [
            'ip' => $ip,
            'duration_minutes' => $minutes,
            'blocked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Add security headers to response.
     */
    protected function addSecurityHeaders(Response $response): Response
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Add CSP header for file upload responses
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; media-src 'self'; object-src 'none'; child-src 'none'; worker-src 'none'; frame-ancestors 'none'; form-action 'self'; base-uri 'self';";
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    /**
     * Handle rate limiting.
     */
    protected function handleRateLimit(Request $request): void
    {
        $key = 'file_upload:' . $request->ip();
        
        RateLimiter::hit($key, 60); // 1 minute decay
    }
}