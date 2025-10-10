<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\InputSanitizationService;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    private InputSanitizationService $sanitizer;

    public function __construct(InputSanitizationService $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $mode = 'standard'): Response
    {
        // Skip sanitization for certain routes or methods
        if ($this->shouldSkipSanitization($request)) {
            return $next($request);
        }

        // Get sanitization options based on mode
        $options = $this->getSanitizationOptions($mode);

        // Sanitize request data
        $this->sanitizeRequestData($request, $options);

        // Check for suspicious input and log if found
        $this->checkForSuspiciousInput($request);

        return $next($request);
    }

    /**
     * Determine if sanitization should be skipped for this request.
     */
    private function shouldSkipSanitization(Request $request): bool
    {
        // Skip for API routes that handle their own sanitization
        if ($request->is('api/*') && $request->hasHeader('X-Skip-Sanitization')) {
            return true;
        }

        // Skip for file upload routes (handled separately)
        if ($request->is('*/upload') || $request->is('*/file/*')) {
            return false; // We still want to sanitize metadata
        }

        // Skip for webhook endpoints
        if ($request->is('webhooks/*')) {
            return true;
        }

        // Skip for specific routes that need raw data
        $skipRoutes = [
            'admin/system/backup',
            'admin/system/restore',
            'admin/logs/raw'
        ];

        foreach ($skipRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get sanitization options based on mode.
     */
    private function getSanitizationOptions(string $mode): array
    {
        switch ($mode) {
            case 'strict':
                return [
                    'mode' => 'strict',
                    'trim' => true,
                    'remove_null_bytes' => true,
                    'normalize_whitespace' => true
                ];

            case 'html':
                return [
                    'mode' => 'html',
                    'trim' => true,
                    'remove_null_bytes' => true,
                    'normalize_whitespace' => false
                ];

            case 'text':
                return [
                    'mode' => 'text',
                    'trim' => true,
                    'remove_null_bytes' => true,
                    'normalize_whitespace' => true
                ];

            default:
                return [
                    'mode' => 'standard',
                    'trim' => true,
                    'remove_null_bytes' => true,
                    'normalize_whitespace' => true
                ];
        }
    }

    /**
     * Sanitize request data.
     */
    private function sanitizeRequestData(Request $request, array $options): void
    {
        // Get field type mappings based on route
        $fieldTypes = $this->getFieldTypesForRoute($request);

        // Sanitize input data
        if ($request->input()) {
            $sanitizedInput = $this->sanitizer->sanitizeFields(
                $request->input(),
                $fieldTypes
            );
            $request->merge($sanitizedInput);
        }

        // Sanitize query parameters
        if ($request->query()) {
            $sanitizedQuery = $this->sanitizer->sanitize($request->query(), $options);
            $request->query->replace($sanitizedQuery);
        }

        // Sanitize file upload metadata (but not the actual files)
        if ($request->hasFile('*')) {
            foreach ($request->allFiles() as $key => $file) {
                if (is_array($file)) {
                    foreach ($file as $index => $singleFile) {
                        $this->sanitizeFileMetadata($request, "{$key}.{$index}");
                    }
                } else {
                    $this->sanitizeFileMetadata($request, $key);
                }
            }
        }
    }

    /**
     * Get field type mappings based on the current route.
     */
    private function getFieldTypesForRoute(Request $request): array
    {
        $route = $request->route();
        if (!$route) {
            return [];
        }

        $routeName = $route->getName() ?? '';
        $routeUri = $route->uri();

        // Student-related routes
        if (str_contains($routeName, 'student') || str_contains($routeUri, 'student')) {
            return $this->sanitizer->getContextConfig('student_form');
        }

        // Teacher-related routes
        if (str_contains($routeName, 'teacher') || str_contains($routeUri, 'teacher')) {
            return $this->sanitizer->getContextConfig('teacher_form');
        }

        // Fee-related routes
        if (str_contains($routeName, 'fee') || str_contains($routeUri, 'fee')) {
            return $this->sanitizer->getContextConfig('fee_form');
        }

        // Attendance-related routes
        if (str_contains($routeName, 'attendance') || str_contains($routeUri, 'attendance')) {
            return $this->sanitizer->getContextConfig('attendance_form');
        }

        // Authentication routes
        if (str_contains($routeName, 'auth') || str_contains($routeUri, 'login') || str_contains($routeUri, 'register')) {
            return [
                'email' => 'email',
                'password' => 'strict',
                'name' => 'name',
                'phone' => 'phone'
            ];
        }

        // Profile routes
        if (str_contains($routeName, 'profile') || str_contains($routeUri, 'profile')) {
            return [
                'name' => 'name',
                'email' => 'email',
                'phone' => 'phone',
                'address' => 'text',
                'bio' => 'html'
            ];
        }

        // Settings routes
        if (str_contains($routeName, 'setting') || str_contains($routeUri, 'setting')) {
            return [
                'value' => 'text',
                'description' => 'html',
                'email' => 'email',
                'url' => 'url'
            ];
        }

        return [];
    }

    /**
     * Sanitize file upload metadata.
     */
    private function sanitizeFileMetadata(Request $request, string $fileKey): void
    {
        $metadata = $request->input("{$fileKey}_metadata", []);
        if (!empty($metadata)) {
            $sanitizedMetadata = $this->sanitizer->sanitizeFileUpload($metadata);
            $request->merge(["{$fileKey}_metadata" => $sanitizedMetadata]);
        }

        // Sanitize file description if provided
        $description = $request->input("{$fileKey}_description");
        if ($description) {
            $sanitizedDescription = $this->sanitizer->sanitize($description, ['mode' => 'text']);
            $request->merge(["{$fileKey}_description" => $sanitizedDescription]);
        }

        // Sanitize file alt text if provided
        $altText = $request->input("{$fileKey}_alt");
        if ($altText) {
            $sanitizedAltText = $this->sanitizer->sanitize($altText, ['mode' => 'text']);
            $request->merge(["{$fileKey}_alt" => $sanitizedAltText]);
        }
    }

    /**
     * Check for suspicious input patterns and log them.
     */
    private function checkForSuspiciousInput(Request $request): void
    {
        $allInput = array_merge(
            $request->input() ?: [],
            $request->query() ?: []
        );

        foreach ($allInput as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            // Check for XSS attempts
            if ($this->sanitizer->containsXSS($value)) {
                $this->sanitizer->logSuspiciousInput($value, 'XSS', [
                    'field' => $key,
                    'route' => $request->route()?->getName(),
                    'method' => $request->method()
                ]);
            }

            // Check for SQL injection attempts
            if ($this->sanitizer->containsSQLInjection($value)) {
                $this->sanitizer->logSuspiciousInput($value, 'SQL_INJECTION', [
                    'field' => $key,
                    'route' => $request->route()?->getName(),
                    'method' => $request->method()
                ]);
            }

            // Check for other suspicious patterns
            if ($this->containsSuspiciousPatterns($value)) {
                $this->sanitizer->logSuspiciousInput($value, 'SUSPICIOUS_PATTERN', [
                    'field' => $key,
                    'route' => $request->route()?->getName(),
                    'method' => $request->method()
                ]);
            }
        }
    }

    /**
     * Check for other suspicious patterns.
     */
    private function containsSuspiciousPatterns(string $input): bool
    {
        $suspiciousPatterns = [
            // Command injection
            '/(\||&|;|`|\$\(|\$\{)/i',
            // Path traversal
            '/(\.\.\/)|(\.\.\\\\)/i',
            // PHP code injection
            '/<\?php/i',
            '/<%/i',
            // Server-side includes
            '/<!--\s*#\s*(exec|include|echo|config|set)/i',
            // LDAP injection
            '/(\*|\(|\)|\||&)/i',
            // XML injection
            '/<!ENTITY/i',
            // Template injection
            '/\{\{.*\}\}/i',
            '/\{%.*%\}/i',
            // NoSQL injection
            '/\$where/i',
            '/\$ne/i',
            '/\$gt/i',
            '/\$lt/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle exceptions during sanitization.
     */
    private function handleSanitizationException(\Exception $e, Request $request): void
    {
        \Log::error('Sanitization error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_id' => auth()->id()
        ]);

        // In production, you might want to throw a validation exception
        // or redirect with an error message
        if (app()->environment('production')) {
            abort(400, 'Invalid input data');
        }
    }
}