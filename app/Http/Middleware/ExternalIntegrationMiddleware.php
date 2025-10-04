<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ExternalIntegrationMiddleware
{
    /**
     * Handle an incoming request for external integrations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Log external integration access attempts
        Log::info('External integration access attempt', [
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now()
        ]);

        // Check for suspicious activity patterns
        if ($this->hasSuspiciousActivity($request)) {
            Log::warning('Suspicious external integration activity detected', [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'endpoint' => $request->path(),
                'reason' => 'Suspicious activity pattern'
            ]);

            return response()->json([
                'error' => 'Access temporarily restricted',
                'message' => 'Please try again later'
            ], 429);
        }

        // Validate file uploads for biometric integration
        if ($request->hasFile('file') && $request->is('*/biometric/*')) {
            $validation = $this->validateBiometricFile($request);
            if (!$validation['valid']) {
                return response()->json([
                    'error' => 'Invalid file',
                    'message' => $validation['message']
                ], 422);
            }
        }

        // Add security headers for external integrations
        $response = $next($request);
        
        if (method_exists($response, 'headers')) {
            $response->headers->set('X-External-Integration', 'true');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
        }

        return $response;
    }

    /**
     * Check for suspicious activity patterns
     */
    private function hasSuspiciousActivity(Request $request): bool
    {
        $userId = Auth::id();
        $ip = $request->ip();
        
        // Check for rapid successive requests
        $requestKey = "external_requests:{$userId}:{$ip}";
        $requestCount = Cache::get($requestKey, 0);
        
        if ($requestCount > 50) { // More than 50 requests per minute
            return true;
        }
        
        Cache::put($requestKey, $requestCount + 1, 60); // 1 minute TTL

        // Check for unusual file upload patterns
        if ($request->hasFile('file')) {
            $uploadKey = "file_uploads:{$userId}";
            $uploadCount = Cache::get($uploadKey, 0);
            
            if ($uploadCount > 10) { // More than 10 file uploads per hour
                return true;
            }
            
            Cache::put($uploadKey, $uploadCount + 1, 3600); // 1 hour TTL
        }

        return false;
    }

    /**
     * Validate biometric file uploads
     */
    private function validateBiometricFile(Request $request): array
    {
        $file = $request->file('file');
        
        // Check file size (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return [
                'valid' => false,
                'message' => 'File size exceeds 10MB limit'
            ];
        }

        // Check file extension
        $allowedExtensions = ['csv', 'xlsx', 'xls'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            return [
                'valid' => false,
                'message' => 'Only CSV and Excel files are allowed'
            ];
        }

        // Check MIME type
        $allowedMimeTypes = [
            'text/csv',
            'application/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return [
                'valid' => false,
                'message' => 'Invalid file type'
            ];
        }

        return ['valid' => true];
    }
}