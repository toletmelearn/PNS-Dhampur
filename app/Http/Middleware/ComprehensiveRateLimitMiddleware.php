<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ComprehensiveRateLimitMiddleware
{
    /**
     * Handle an incoming request with comprehensive rate limiting
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $type
     * @param  string|null  $endpoint
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $type = 'general', ?string $endpoint = null)
    {
        $config = config('ratelimit');
        $user = Auth::user();
        $ip = $request->ip();
        $userRole = $user ? $user->role : 'guest';
        
        // Determine endpoint if not provided
        $endpoint = $endpoint ?? $this->determineEndpoint($request);
        
        // Apply rate limiting based on type
        switch ($type) {
            case 'login':
                return $this->handleLoginRateLimit($request, $next, $config);
                
            case 'api':
                return $this->handleApiRateLimit($request, $next, $config, $endpoint, $userRole);
                
            case 'form':
                return $this->handleFormRateLimit($request, $next, $config, $endpoint, $userRole);
                
            case 'download':
                return $this->handleDownloadRateLimit($request, $next, $config, $userRole);
                
            case 'upload':
                return $this->handleUploadRateLimit($request, $next, $config, $userRole);
                
            default:
                return $this->handleGeneralRateLimit($request, $next, $config, $userRole);
        }
    }
    
    /**
     * Handle login rate limiting
     */
    private function handleLoginRateLimit(Request $request, Closure $next, array $config)
    {
        $ip = $request->ip();
        $email = $request->input('email', '');
        
        $ipKey = "login_ip:{$ip}";
        $emailKey = "login_email:{$email}";
        $globalKey = "login_global";
        $rapidKey = "login_rapid:{$ip}";
        
        // Check IP-based rate limiting
        if (RateLimiter::tooManyAttempts($ipKey, $config['login']['ip_limit'])) {
            $this->logRateLimitExceeded('login_ip', $ip, $request);
            return $this->createRateLimitResponse($request, 'login', 'IP address', RateLimiter::availableIn($ipKey));
        }
        
        // Check email-based rate limiting
        if ($email && RateLimiter::tooManyAttempts($emailKey, $config['login']['email_limit'])) {
            $this->logRateLimitExceeded('login_email', $email, $request);
            return $this->createRateLimitResponse($request, 'login', 'email address', RateLimiter::availableIn($emailKey));
        }
        
        // Check rapid attempts
        if (RateLimiter::tooManyAttempts($rapidKey, $config['login']['rapid_limit'])) {
            $this->logSuspiciousActivity('rapid_login', $request);
            return $this->createRateLimitResponse($request, 'login', 'rapid attempts', RateLimiter::availableIn($rapidKey));
        }
        
        // Check global limit
        if (RateLimiter::tooManyAttempts($globalKey, $config['login']['global_limit'])) {
            $this->logRateLimitExceeded('login_global', 'system', $request);
            return $this->createSystemOverloadResponse($request);
        }
        
        // Increment counters
        RateLimiter::hit($ipKey, $config['login']['ip_window'] * 60);
        if ($email) {
            RateLimiter::hit($emailKey, $config['login']['email_window'] * 60);
        }
        RateLimiter::hit($rapidKey, $config['login']['rapid_window']);
        RateLimiter::hit($globalKey, $config['login']['global_window'] * 60);
        
        return $next($request);
    }
    
    /**
     * Handle API rate limiting
     */
    private function handleApiRateLimit(Request $request, Closure $next, array $config, string $endpoint, string $userRole)
    {
        $user = Auth::user();
        $ip = $request->ip();
        
        $userKey = $user ? "api_user:{$user->id}" : "api_ip:{$ip}";
        $endpointKey = "api_endpoint:{$endpoint}:" . ($user ? $user->id : $ip);
        $globalKey = "api_global";
        $burstKey = $user ? "api_burst_user:{$user->id}" : "api_burst_ip:{$ip}";
        
        // Get role-based limit
        $roleLimit = $config['api']['role_limits'][$userRole] ?? $config['api']['role_limits']['guest'];
        
        // Get endpoint-specific limit
        $endpointLimit = $config['api']['endpoint_limits'][$endpoint] ?? 50;
        
        // Check role-based rate limiting
        if (RateLimiter::tooManyAttempts($userKey, $roleLimit)) {
            $this->logRateLimitExceeded('api_role', $userRole, $request);
            return $this->createRateLimitResponse($request, 'api', 'role-based', RateLimiter::availableIn($userKey));
        }
        
        // Check endpoint-specific rate limiting
        if (RateLimiter::tooManyAttempts($endpointKey, $endpointLimit)) {
            $this->logRateLimitExceeded('api_endpoint', $endpoint, $request);
            return $this->createRateLimitResponse($request, 'api', 'endpoint-specific', RateLimiter::availableIn($endpointKey));
        }
        
        // Check burst detection
        if ($config['api']['burst_detection']['enabled'] && 
            RateLimiter::tooManyAttempts($burstKey, $config['api']['burst_detection']['threshold'])) {
            $this->logSuspiciousActivity('api_burst', $request);
            return $this->createRateLimitResponse($request, 'api', 'burst detection', RateLimiter::availableIn($burstKey));
        }
        
        // Check global API limit
        if (RateLimiter::tooManyAttempts($globalKey, $config['api']['global_limit'])) {
            $this->logRateLimitExceeded('api_global', 'system', $request);
            return $this->createSystemOverloadResponse($request);
        }
        
        // Increment counters
        RateLimiter::hit($userKey, $config['api']['window'] * 60);
        RateLimiter::hit($endpointKey, $config['api']['window'] * 60);
        if ($config['api']['burst_detection']['enabled']) {
            RateLimiter::hit($burstKey, $config['api']['burst_detection']['window']);
        }
        RateLimiter::hit($globalKey, $config['api']['window'] * 60);
        
        $response = $next($request);
        
        // Add rate limit headers
        return $this->addRateLimitHeaders($response, $roleLimit, RateLimiter::attempts($userKey));
    }
    
    /**
     * Handle form submission rate limiting
     */
    private function handleFormRateLimit(Request $request, Closure $next, array $config, string $endpoint, string $userRole)
    {
        $user = Auth::user();
        $ip = $request->ip();
        
        $userKey = $user ? "form_user:{$user->id}" : "form_ip:{$ip}";
        $formKey = "form_type:{$endpoint}:" . ($user ? $user->id : $ip);
        $rapidKey = $user ? "form_rapid_user:{$user->id}" : "form_rapid_ip:{$ip}";
        $globalKey = "form_global_critical";
        
        // Get base limit and apply role multiplier
        $baseLimit = $config['form']['default_limit'];
        $roleMultiplier = $config['form']['role_multipliers'][$userRole] ?? 1.0;
        $userLimit = intval($baseLimit * $roleMultiplier);
        
        // Get form-specific limit
        $formLimit = $config['form']['critical_forms'][$endpoint] ?? $baseLimit;
        $formLimit = intval($formLimit * $roleMultiplier);
        
        // Check user-based rate limiting
        if (RateLimiter::tooManyAttempts($userKey, $userLimit)) {
            $this->logRateLimitExceeded('form_user', $userRole, $request);
            return $this->createRateLimitResponse($request, 'form', 'user-based', RateLimiter::availableIn($userKey));
        }
        
        // Check form-specific rate limiting
        if (RateLimiter::tooManyAttempts($formKey, $formLimit)) {
            $this->logRateLimitExceeded('form_type', $endpoint, $request);
            return $this->createRateLimitResponse($request, 'form', 'form-specific', RateLimiter::availableIn($formKey));
        }
        
        // Check rapid submission detection
        if ($config['form']['rapid_detection']['enabled'] && 
            RateLimiter::tooManyAttempts($rapidKey, $config['form']['rapid_detection']['limit'])) {
            $this->logSuspiciousActivity('form_rapid', $request);
            return $this->createRateLimitResponse($request, 'form', 'rapid submission', RateLimiter::availableIn($rapidKey));
        }
        
        // Check global critical form limit
        if (in_array($endpoint, array_keys($config['form']['critical_forms'])) && 
            RateLimiter::tooManyAttempts($globalKey, $config['form']['global_critical_limit'])) {
            $this->logRateLimitExceeded('form_global_critical', 'system', $request);
            return $this->createSystemOverloadResponse($request);
        }
        
        // Increment counters
        RateLimiter::hit($userKey, $config['form']['default_window'] * 60);
        RateLimiter::hit($formKey, $config['form']['default_window'] * 60);
        if ($config['form']['rapid_detection']['enabled']) {
            RateLimiter::hit($rapidKey, $config['form']['rapid_detection']['window']);
        }
        if (in_array($endpoint, array_keys($config['form']['critical_forms']))) {
            RateLimiter::hit($globalKey, $config['form']['default_window'] * 60);
        }
        
        return $next($request);
    }
    
    /**
     * Handle download rate limiting
     */
    private function handleDownloadRateLimit(Request $request, Closure $next, array $config, string $userRole)
    {
        $user = Auth::user();
        $ip = $request->ip();
        $fileType = $this->detectFileType($request);
        
        $userKey = $user ? "download_user:{$user->id}" : "download_ip:{$ip}";
        $bandwidthKey = $user ? "bandwidth_user:{$user->id}" : "bandwidth_ip:{$ip}";
        $rapidKey = $user ? "download_rapid_user:{$user->id}" : "download_rapid_ip:{$ip}";
        $globalKey = "download_global";
        
        // Get role-based limits
        $roleLimits = $config['download']['role_limits'][$userRole] ?? $config['download']['role_limits']['guest'];
        $countLimit = $roleLimits['count'];
        $bandwidthLimit = $roleLimits['bandwidth'];
        
        // Apply file type multiplier
        $fileTypeMultiplier = $config['download']['file_type_multipliers'][$fileType] ?? 1;
        $adjustedCountLimit = intval($countLimit / $fileTypeMultiplier);
        
        // Check download count limit
        if (RateLimiter::tooManyAttempts($userKey, $adjustedCountLimit)) {
            $this->logRateLimitExceeded('download_count', $userRole, $request);
            return $this->createRateLimitResponse($request, 'download', 'count limit', RateLimiter::availableIn($userKey));
        }
        
        // Check bandwidth limit
        $currentBandwidth = Cache::get($bandwidthKey, 0);
        if ($currentBandwidth >= $bandwidthLimit) {
            $this->logRateLimitExceeded('download_bandwidth', $userRole, $request);
            return $this->createRateLimitResponse($request, 'download', 'bandwidth limit', 3600);
        }
        
        // Check rapid download detection
        if ($config['download']['rapid_detection']['enabled'] && 
            RateLimiter::tooManyAttempts($rapidKey, $config['download']['rapid_detection']['limit'])) {
            $this->logSuspiciousActivity('download_rapid', $request);
            return $this->createRateLimitResponse($request, 'download', 'rapid detection', RateLimiter::availableIn($rapidKey));
        }
        
        // Check global download limit
        if (RateLimiter::tooManyAttempts($globalKey, $config['download']['global_limit'])) {
            $this->logRateLimitExceeded('download_global', 'system', $request);
            return $this->createSystemOverloadResponse($request);
        }
        
        // Increment counters
        RateLimiter::hit($userKey, $config['download']['window'] * 60);
        if ($config['download']['rapid_detection']['enabled']) {
            RateLimiter::hit($rapidKey, $config['download']['rapid_detection']['window']);
        }
        RateLimiter::hit($globalKey, $config['download']['window'] * 60);
        
        $response = $next($request);
        
        // Track bandwidth usage after successful download
        if ($response->getStatusCode() === 200) {
            $this->trackBandwidthUsage($bandwidthKey, $response, $fileType);
        }
        
        return $response;
    }
    
    /**
     * Handle upload rate limiting
     */
    private function handleUploadRateLimit(Request $request, Closure $next, array $config, string $userRole)
    {
        $user = Auth::user();
        $ip = $request->ip();
        
        $userKey = $user ? "upload_user:{$user->id}" : "upload_ip:{$ip}";
        $sizeKey = $user ? "upload_size_user:{$user->id}" : "upload_size_ip:{$ip}";
        $rapidKey = $user ? "upload_rapid_user:{$user->id}" : "upload_rapid_ip:{$ip}";
        $globalKey = "upload_global";
        
        // Role-based upload limits (uploads per hour)
        $roleLimits = [
            'super_admin' => 500,
            'admin' => 300,
            'principal' => 200,
            'teacher' => 100,
            'student' => 50,
            'parent' => 20,
            'guest' => 5
        ];
        
        $uploadLimit = $roleLimits[$userRole] ?? $roleLimits['guest'];
        
        // Check upload count limit
        if (RateLimiter::tooManyAttempts($userKey, $uploadLimit)) {
            $this->logRateLimitExceeded('upload_count', $userRole, $request);
            return $this->createRateLimitResponse($request, 'upload', 'count limit', RateLimiter::availableIn($userKey));
        }
        
        // Check rapid upload detection (10 uploads in 1 minute)
        if (RateLimiter::tooManyAttempts($rapidKey, 10)) {
            $this->logSuspiciousActivity('upload_rapid', $request);
            return $this->createRateLimitResponse($request, 'upload', 'rapid detection', RateLimiter::availableIn($rapidKey));
        }
        
        // Check global upload limit
        if (RateLimiter::tooManyAttempts($globalKey, 5000)) {
            $this->logRateLimitExceeded('upload_global', 'system', $request);
            return $this->createSystemOverloadResponse($request);
        }
        
        // Increment counters
        RateLimiter::hit($userKey, 3600); // 1 hour
        RateLimiter::hit($rapidKey, 60);  // 1 minute
        RateLimiter::hit($globalKey, 3600); // 1 hour
        
        return $next($request);
    }
    
    /**
     * Handle general rate limiting
     */
    private function handleGeneralRateLimit(Request $request, Closure $next, array $config, string $userRole)
    {
        $user = Auth::user();
        $ip = $request->ip();
        
        $userKey = $user ? "general_user:{$user->id}" : "general_ip:{$ip}";
        
        // Role-based general limits (requests per minute)
        $roleLimits = [
            'super_admin' => 200,
            'admin' => 150,
            'principal' => 100,
            'teacher' => 80,
            'student' => 60,
            'parent' => 40,
            'guest' => 20
        ];
        
        $generalLimit = $roleLimits[$userRole] ?? $roleLimits['guest'];
        
        // Check general rate limit
        if (RateLimiter::tooManyAttempts($userKey, $generalLimit)) {
            $this->logRateLimitExceeded('general', $userRole, $request);
            return $this->createRateLimitResponse($request, 'general', 'general limit', RateLimiter::availableIn($userKey));
        }
        
        // Increment counter
        RateLimiter::hit($userKey, 60); // 1 minute
        
        $response = $next($request);
        
        // Add rate limit headers
        return $this->addRateLimitHeaders($response, $generalLimit, RateLimiter::attempts($userKey));
    }
    
    /**
     * Determine endpoint from request
     */
    private function determineEndpoint(Request $request): string
    {
        $route = $request->route();
        if ($route && $route->getName()) {
            return $route->getName();
        }
        
        $path = $request->path();
        $method = $request->method();
        
        // Map common patterns to endpoint names
        $patterns = [
            'POST:/api/login' => 'auth.login',
            'POST:/api/register' => 'auth.register',
            'POST:/api/logout' => 'auth.logout',
            'POST:/password/reset' => 'password.reset',
            'POST:/users' => 'users.create',
            'PUT:/users' => 'users.update',
            'DELETE:/users' => 'users.delete',
            'GET:/reports' => 'reports.generate',
            'POST:/files/upload' => 'files.upload',
            'GET:/files/download' => 'files.download',
        ];
        
        $key = $method . ':/' . $path;
        return $patterns[$key] ?? 'general';
    }
    
    /**
     * Detect file type from request
     */
    private function detectFileType(Request $request): string
    {
        $path = $request->path();
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        $typeMap = [
            'pdf' => 'document',
            'doc' => 'document',
            'docx' => 'document',
            'xls' => 'document',
            'xlsx' => 'document',
            'ppt' => 'document',
            'pptx' => 'document',
            'txt' => 'document',
            
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'bmp' => 'image',
            'svg' => 'image',
            
            'mp4' => 'video',
            'avi' => 'video',
            'mov' => 'video',
            'wmv' => 'video',
            'flv' => 'video',
            'webm' => 'video',
            
            'mp3' => 'audio',
            'wav' => 'audio',
            'flac' => 'audio',
            'aac' => 'audio',
            'ogg' => 'audio',
            
            'zip' => 'archive',
            'rar' => 'archive',
            '7z' => 'archive',
            'tar' => 'archive',
            'gz' => 'archive',
        ];
        
        return $typeMap[$extension] ?? 'other';
    }
    
    /**
     * Track bandwidth usage
     */
    private function trackBandwidthUsage(string $key, $response, string $fileType): void
    {
        $contentLength = $response->headers->get('Content-Length', 0);
        if ($contentLength > 0) {
            $currentUsage = Cache::get($key, 0);
            Cache::put($key, $currentUsage + $contentLength, 3600); // 1 hour TTL
        }
    }
    
    /**
     * Create rate limit response
     */
    private function createRateLimitResponse(Request $request, string $type, string $reason, int $retryAfter)
    {
        $config = config('ratelimit');
        $message = $config['responses']["{$type}_blocked"] ?? "Rate limit exceeded for {$type}. Please try again later.";
        $message = str_replace([':minutes', ':seconds'], [ceil($retryAfter / 60), $retryAfter], $message);
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Rate Limit Exceeded',
                'message' => $message,
                'code' => strtoupper($type) . '_RATE_LIMIT_EXCEEDED',
                'type' => $type,
                'reason' => $reason,
                'retry_after' => $retryAfter,
                'timestamp' => now()->toISOString()
            ], 429)->header('Retry-After', $retryAfter);
        }
        
        return response($message, 429)
            ->header('Retry-After', $retryAfter)
            ->header('Content-Type', 'text/plain');
    }
    
    /**
     * Create system overload response
     */
    private function createSystemOverloadResponse(Request $request)
    {
        $config = config('ratelimit');
        $message = $config['responses']['global_limit'] ?? 'System is currently overloaded. Please try again later.';
        
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'System Overload',
                'message' => $message,
                'code' => 'SYSTEM_OVERLOAD',
                'retry_after' => 300, // 5 minutes
                'timestamp' => now()->toISOString()
            ], 503)->header('Retry-After', 300);
        }
        
        return response($message, 503)
            ->header('Retry-After', 300)
            ->header('Content-Type', 'text/plain');
    }
    
    /**
     * Add rate limit headers to response
     */
    private function addRateLimitHeaders($response, int $limit, int $attempts)
    {
        $config = config('ratelimit');
        
        if ($config['headers']['include_headers']) {
            $response->headers->add([
                $config['headers']['limit_header'] => $limit,
                $config['headers']['remaining_header'] => max(0, $limit - $attempts),
                $config['headers']['reset_header'] => now()->addMinute()->timestamp,
            ]);
        }
        
        return $response;
    }
    
    /**
     * Log rate limit exceeded event
     */
    private function logRateLimitExceeded(string $type, string $identifier, Request $request): void
    {
        Log::channel('security')->warning('Rate limit exceeded', [
            'type' => $type,
            'identifier' => $identifier,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity(string $type, Request $request): void
    {
        Log::channel('security')->alert('Suspicious activity detected', [
            'type' => $type,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString()
        ]);
    }
}