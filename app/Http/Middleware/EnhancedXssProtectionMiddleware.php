<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\HtmlPurificationService;
use Symfony\Component\HttpFoundation\Response;

class EnhancedXssProtectionMiddleware
{
    protected HtmlPurificationService $purificationService;

    /**
     * Fields that should be purified as HTML content
     */
    protected array $htmlFields = [
        'description', 'content', 'message', 'notes', 'remarks', 
        'bio', 'about', 'details', 'comment', 'review'
    ];

    /**
     * Fields that should be sanitized as plain text
     */
    protected array $textFields = [
        'name', 'title', 'subject', 'address', 'city', 'state',
        'qualification', 'experience', 'skills'
    ];

    /**
     * Fields that should be sanitized as file names
     */
    protected array $fileFields = [
        'filename', 'file_name', 'document_name', 'image_name'
    ];

    /**
     * Routes that should skip XSS protection (API endpoints with their own validation)
     */
    protected array $skipRoutes = [
        'api/upload/raw',
        'api/import/csv'
    ];

    public function __construct(HtmlPurificationService $purificationService)
    {
        $this->purificationService = $purificationService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip protection for certain routes
        if ($this->shouldSkipProtection($request)) {
            return $next($request);
        }

        // Apply XSS protection to request data
        $this->protectRequestData($request);

        $response = $next($request);

        // Add XSS protection headers
        $this->addXssProtectionHeaders($response);

        return $response;
    }

    /**
     * Check if protection should be skipped for this request
     */
    protected function shouldSkipProtection(Request $request): bool
    {
        $path = $request->path();
        
        foreach ($this->skipRoutes as $skipRoute) {
            if (str_starts_with($path, $skipRoute)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply XSS protection to request data
     */
    protected function protectRequestData(Request $request): void
    {
        $input = $request->all();
        $protectedInput = $this->protectData($input, $request);
        
        // Replace request data with protected data
        $request->replace($protectedInput);
    }

    /**
     * Recursively protect data based on field types
     */
    protected function protectData(array $data, Request $request, string $parentKey = ''): array
    {
        $protected = [];

        foreach ($data as $key => $value) {
            $fullKey = $parentKey ? $parentKey . '.' . $key : $key;

            if (is_array($value)) {
                $protected[$key] = $this->protectData($value, $request, $fullKey);
            } elseif (is_string($value)) {
                $protected[$key] = $this->protectStringValue($key, $value, $fullKey, $request);
            } else {
                $protected[$key] = $value;
            }
        }

        return $protected;
    }

    /**
     * Protect individual string values based on field type
     */
    protected function protectStringValue(string $key, string $value, string $fullKey, Request $request): string
    {
        // Skip empty values
        if (empty(trim($value))) {
            return $value;
        }

        // Check for potential XSS first
        if ($this->purificationService->containsXss($value)) {
            $this->logXssAttempt($request, $fullKey, $value);
        }

        // Determine protection type based on field name
        if ($this->isHtmlField($key)) {
            return $this->protectHtmlField($value, $key);
        } elseif ($this->isFileField($key)) {
            return $this->purificationService->sanitizeFileName($value);
        } else {
            return $this->purificationService->sanitizeText($value);
        }
    }

    /**
     * Check if field should be treated as HTML content
     */
    protected function isHtmlField(string $fieldName): bool
    {
        $fieldName = strtolower($fieldName);
        
        foreach ($this->htmlFields as $htmlField) {
            if (str_contains($fieldName, $htmlField)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if field should be treated as a file name
     */
    protected function isFileField(string $fieldName): bool
    {
        $fieldName = strtolower($fieldName);
        
        foreach ($this->fileFields as $fileField) {
            if (str_contains($fieldName, $fileField)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Protect HTML content fields
     */
    protected function protectHtmlField(string $value, string $fieldName): string
    {
        // Determine content type for appropriate configuration
        $contentType = $this->determineContentType($fieldName);
        $config = $this->purificationService->getConfigForContentType($contentType);
        
        return $this->purificationService->purify($value, $config);
    }

    /**
     * Determine content type based on field name
     */
    protected function determineContentType(string $fieldName): string
    {
        $fieldName = strtolower($fieldName);

        if (str_contains($fieldName, 'comment') || str_contains($fieldName, 'review')) {
            return 'comment';
        } elseif (str_contains($fieldName, 'description') || str_contains($fieldName, 'about')) {
            return 'description';
        } else {
            return 'rich_text';
        }
    }

    /**
     * Log XSS attempt for security monitoring
     */
    protected function logXssAttempt(Request $request, string $field, string $value): void
    {
        Log::warning('XSS attempt detected', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'field' => $field,
            'value_length' => strlen($value),
            'value_preview' => substr($value, 0, 200),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);

        // Optionally trigger security alert
        $this->triggerSecurityAlert($request, $field, $value);
    }

    /**
     * Trigger security alert for severe XSS attempts
     */
    protected function triggerSecurityAlert(Request $request, string $field, string $value): void
    {
        // Check for severe XSS patterns
        $severePatterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript\s*:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>/i'
        ];

        foreach ($severePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                // Log as critical security event
                Log::critical('Critical XSS attempt detected', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                    'field' => $field,
                    'pattern_matched' => $pattern,
                    'user_id' => auth()->id()
                ]);

                // Here you could integrate with security monitoring systems
                // or send immediate alerts to administrators
                break;
            }
        }
    }

    /**
     * Add XSS protection headers to response
     */
    protected function addXssProtectionHeaders(Response $response): void
    {
        // Enhanced XSS Protection header
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Content Type Options
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enhanced Content Security Policy for XSS protection
        if (!$response->headers->has('Content-Security-Policy')) {
            $csp = $this->buildContentSecurityPolicy();
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Cross-Origin Embedder Policy
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');

        // Cross-Origin Opener Policy
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
    }

    /**
     * Build Content Security Policy for XSS protection
     */
    protected function buildContentSecurityPolicy(): string
    {
        $policies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "media-src 'self'",
            "child-src 'none'",
            "worker-src 'none'"
        ];

        return implode('; ', $policies);
    }

    /**
     * Configure field types for specific forms
     */
    public function configureFieldTypes(array $htmlFields = null, array $textFields = null, array $fileFields = null): self
    {
        if ($htmlFields !== null) {
            $this->htmlFields = array_merge($this->htmlFields, $htmlFields);
        }

        if ($textFields !== null) {
            $this->textFields = array_merge($this->textFields, $textFields);
        }

        if ($fileFields !== null) {
            $this->fileFields = array_merge($this->fileFields, $fileFields);
        }

        return $this;
    }
}