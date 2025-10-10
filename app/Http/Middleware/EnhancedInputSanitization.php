<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\InputSanitizationTrait;

class EnhancedInputSanitization
{
    use InputSanitizationTrait;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip sanitization for certain routes or content types
        if ($this->shouldSkipSanitization($request)) {
            return $next($request);
        }

        try {
            // Sanitize request input
            $this->sanitizeRequest($request);
            
            // Log suspicious activity
            $this->detectSuspiciousActivity($request);
            
        } catch (\Exception $e) {
            Log::error('Input sanitization error', [
                'error' => $e->getMessage(),
                'url' => $request->url(),
                'method' => $request->method(),
                'ip' => $request->ip()
            ]);
        }

        return $next($request);
    }

    /**
     * Determine if sanitization should be skipped
     *
     * @param Request $request
     * @return bool
     */
    protected function shouldSkipSanitization(Request $request): bool
    {
        // Skip for API endpoints that handle raw data
        $skipRoutes = [
            'api/webhook/*',
            'api/external/*',
            'admin/logs/*'
        ];

        foreach ($skipRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        // Skip for file uploads (handled separately)
        if ($request->hasFile('*')) {
            return false; // We still want to sanitize other fields
        }

        // Skip for JSON API requests with specific content types
        if ($request->isJson() && $request->header('Content-Type') === 'application/json') {
            return false; // We still sanitize JSON content
        }

        return false;
    }

    /**
     * Sanitize the request input
     *
     * @param Request $request
     * @return void
     */
    protected function sanitizeRequest(Request $request): void
    {
        // Get all input except files
        $input = $request->except(['_token', '_method']);
        
        if (empty($input)) {
            return;
        }

        // Define sanitization rules based on common field patterns
        $sanitizationRules = $this->getSanitizationRules($request);
        
        // Sanitize the input
        $sanitizedInput = $this->sanitizeRequestInput($input, $sanitizationRules);
        
        // Replace the request input with sanitized data
        $request->replace($sanitizedInput);
    }

    /**
     * Get sanitization rules based on field names and request context
     *
     * @param Request $request
     * @return array
     */
    protected function getSanitizationRules(Request $request): array
    {
        $rules = [];
        
        foreach ($request->all() as $key => $value) {
            // Email fields
            if (str_contains($key, 'email') || $key === 'email') {
                $rules[$key] = 'email';
            }
            // Phone fields
            elseif (str_contains($key, 'phone') || str_contains($key, 'mobile') || str_contains($key, 'contact')) {
                $rules[$key] = 'phone';
            }
            // URL fields
            elseif (str_contains($key, 'url') || str_contains($key, 'website') || str_contains($key, 'link')) {
                $rules[$key] = 'url';
            }
            // Numeric fields
            elseif (str_contains($key, 'amount') || str_contains($key, 'price') || str_contains($key, 'salary') || 
                    str_contains($key, 'fee') || str_contains($key, 'cost')) {
                $rules[$key] = 'float';
            }
            // Integer fields
            elseif (str_contains($key, 'id') || str_contains($key, 'count') || str_contains($key, 'number') ||
                    str_contains($key, 'year') || str_contains($key, 'age') || str_contains($key, 'quantity')) {
                $rules[$key] = 'integer';
            }
            // Search fields
            elseif (str_contains($key, 'search') || str_contains($key, 'query') || str_contains($key, 'term')) {
                $rules[$key] = 'search';
            }
            // Array fields
            elseif (is_array($value)) {
                $rules[$key] = 'array';
            }
            // JSON fields
            elseif (str_contains($key, 'json') || str_contains($key, 'data') || str_contains($key, 'metadata')) {
                if (is_string($value) && $this->isJson($value)) {
                    $rules[$key] = 'json';
                } else {
                    $rules[$key] = 'string';
                }
            }
            // Default to string
            else {
                $rules[$key] = 'string';
            }
        }

        return $rules;
    }

    /**
     * Detect suspicious activity in the request
     *
     * @param Request $request
     * @return void
     */
    protected function detectSuspiciousActivity(Request $request): void
    {
        $suspiciousPatterns = [
            // SQL Injection patterns
            '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            // XSS patterns
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            // Path traversal
            '/\.\.[\/\\\\]/',
            // Command injection
            '/[;&|`$(){}[\]]/i',
        ];

        $input = json_encode($request->all());
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                Log::warning('Suspicious input detected', [
                    'pattern' => $pattern,
                    'url' => $request->url(),
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'user_id' => auth()->id(),
                    'input_sample' => substr($input, 0, 500)
                ]);
                break;
            }
        }
    }

    /**
     * Check if a string is valid JSON
     *
     * @param string $string
     * @return bool
     */
    protected function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}