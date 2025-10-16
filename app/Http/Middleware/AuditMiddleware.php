<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Carbon\Carbon;

class AuditMiddleware
{
    /**
     * Actions that should be audited
     */
    private array $auditableActions = [
        'POST', 'PUT', 'PATCH', 'DELETE'
    ];

    /**
     * Sensitive routes that require detailed auditing
     */
    private array $sensitiveRoutes = [
        'users.*',
        'students.*',
        'teachers.*',
        'fees.*',
        'exams.*',
        'settings.*',
        'admin.*'
    ];

    /**
     * Fields to exclude from audit logs for privacy
     */
    private array $excludedFields = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'api_key',
        'secret',
        '_token',
        '_method'
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Capture request data before processing
        $requestData = $this->captureRequestData($request);

        // Process the request
        $response = $next($request);

        // Calculate performance metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;

        // Determine if this request should be audited
        if ($this->shouldAudit($request, $response)) {
            $this->createAuditLog($request, $response, $requestData, $executionTime, $memoryUsage);
        }

        // Log performance metrics for slow requests
        if ($executionTime > 1000) { // Log requests taking more than 1 second
            $this->logSlowRequest($request, $executionTime, $memoryUsage);
        }

        // Add audit headers to response
        $this->addAuditHeaders($response, $executionTime);

        return $response;
    }

    /**
     * Capture request data for auditing
     */
    private function captureRequestData(Request $request): array
    {
        $data = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('Referer'),
            'timestamp' => Carbon::now()->toISOString(),
            'session_id' => session()->getId(),
            'request_id' => Str::uuid()->toString()
        ];

        // Add user information if authenticated
        if (Auth::check()) {
            $user = Auth::user();
            $data['user'] = [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'roles' => method_exists($user, 'roles') ? $user->roles->pluck('name')->toArray() : []
            ];
        }

        // Add request parameters (excluding sensitive data)
        if (in_array($request->method(), $this->auditableActions)) {
            $data['input'] = $this->sanitizeInput($request->all());
        }

        // Add route information
        $route = $request->route();
        if ($route) {
            $data['route'] = [
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'parameters' => $route->parameters()
            ];
        }

        return $data;
    }

    /**
     * Determine if the request should be audited
     */
    private function shouldAudit(Request $request, Response $response): bool
    {
        // Skip logging for certain routes
        $skipRoutes = [
            'api/heartbeat',
            'api/health',
            '_debugbar',
            'telescope',
            'horizon',
            'nova',
            'livewire',
        ];

        $path = $request->path();
        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($path, $skipRoute)) {
                return false;
            }
        }

        // Skip GET requests to assets, images, etc.
        if ($request->method() === 'GET' && preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $path)) {
            return false;
        }

        // Always audit state-changing operations
        if (in_array($request->method(), $this->auditableActions)) {
            return true;
        }

        // Audit sensitive routes
        $routeName = $request->route() ? $request->route()->getName() : '';
        foreach ($this->sensitiveRoutes as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        // Audit failed requests
        if ($response->getStatusCode() >= 400) {
            return true;
        }

        // Audit authentication-related requests
        if ($request->is('login') || $request->is('logout') || $request->is('register')) {
            return true;
        }

        // Audit file uploads
        if ($request->hasFile('*')) {
            return true;
        }

        return false;
    }

    /**
     * Create audit log entry
     */
    private function createAuditLog(Request $request, Response $response, array $requestData, float $executionTime, int $memoryUsage): void
    {
        try {
            $auditData = array_merge($requestData, [
                'response' => [
                    'status_code' => $response->getStatusCode(),
                    'content_type' => $response->headers->get('Content-Type'),
                    'content_length' => strlen($response->getContent())
                ],
                'performance' => [
                    'execution_time_ms' => round($executionTime, 2),
                    'memory_usage_bytes' => $memoryUsage,
                    'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2)
                ],
                'context' => $this->getAuditContext($request, $response)
            ]);

            // Store in database
            DB::table('audit_logs')->insert([
                'user_id' => Auth::id(),
                'action' => $this->getActionDescription($request),
                'model_type' => $this->getModelType($request),
                'model_id' => $this->getModelId($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'route_name' => $request->route() ? $request->route()->getName() : null,
                'request_data' => json_encode($this->sanitizeInput($request->all())),
                'response_code' => $response->getStatusCode(),
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'session_id' => session()->getId(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log to file for backup
            Log::channel('audit')->info('Audit Log', $auditData);

        } catch (\Exception $e) {
            // Don't let audit logging break the application
            Log::error('Failed to create audit log', [
                'error' => $e->getMessage(),
                'request_url' => $request->fullUrl(),
                'user_id' => Auth::id()
            ]);
        }
    }

    /**
     * Get action description for audit log
     */
    private function getActionDescription(Request $request): string
    {
        $method = $request->method();
        $routeName = $request->route() ? $request->route()->getName() : '';
        
        // Map common actions
        $actionMap = [
            'POST' => 'Created',
            'PUT' => 'Updated',
            'PATCH' => 'Updated',
            'DELETE' => 'Deleted',
            'GET' => 'Viewed'
        ];

        $baseAction = $actionMap[$method] ?? $method;

        // Add context based on route
        if (Str::contains($routeName, 'student')) {
            return $baseAction . ' Student';
        } elseif (Str::contains($routeName, 'teacher')) {
            return $baseAction . ' Teacher';
        } elseif (Str::contains($routeName, 'attendance')) {
            return $baseAction . ' Attendance';
        } elseif (Str::contains($routeName, 'fee')) {
            return $baseAction . ' Fee';
        } elseif (Str::contains($routeName, 'exam')) {
            return $baseAction . ' Exam';
        } elseif (Str::contains($routeName, 'user')) {
            return $baseAction . ' User';
        }

        return $baseAction . ' Resource';
    }

    /**
     * Get model type from request
     */
    private function getModelType(Request $request): ?string
    {
        $routeName = $request->route() ? $request->route()->getName() : '';
        
        if (Str::contains($routeName, 'student')) {
            return 'Student';
        } elseif (Str::contains($routeName, 'teacher')) {
            return 'Teacher';
        } elseif (Str::contains($routeName, 'attendance')) {
            return 'Attendance';
        } elseif (Str::contains($routeName, 'fee')) {
            return 'Fee';
        } elseif (Str::contains($routeName, 'exam')) {
            return 'Exam';
        } elseif (Str::contains($routeName, 'user')) {
            return 'User';
        }

        return null;
    }

    /**
     * Get model ID from request
     */
    private function getModelId(Request $request): ?int
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        // Try to get ID from route parameters
        $parameters = $route->parameters();
        
        // Common parameter names for IDs
        $idParams = ['id', 'student', 'teacher', 'user', 'attendance', 'fee', 'exam'];
        
        foreach ($idParams as $param) {
            if (isset($parameters[$param]) && is_numeric($parameters[$param])) {
                return (int) $parameters[$param];
            }
        }

        return null;
    }

    /**
     * Get additional audit context
     */
    private function getAuditContext(Request $request, Response $response): array
    {
        $context = [];

        // Add file upload information
        if ($request->hasFile('*')) {
            $files = [];
            foreach ($request->allFiles() as $key => $file) {
                if (is_array($file)) {
                    foreach ($file as $f) {
                        $files[] = [
                            'field' => $key,
                            'name' => $f->getClientOriginalName(),
                            'size' => $f->getSize(),
                            'mime' => $f->getMimeType()
                        ];
                    }
                } else {
                    $files[] = [
                        'field' => $key,
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime' => $file->getMimeType()
                    ];
                }
            }
            $context['files'] = $files;
        }

        // Add error information for failed requests
        if ($response->getStatusCode() >= 400) {
            $context['error'] = [
                'status_code' => $response->getStatusCode(),
                'status_text' => Response::$statusTexts[$response->getStatusCode()] ?? 'Unknown'
            ];
        }

        // Add bulk operation information
        if ($request->has('bulk_action') || $request->has('selected_ids')) {
            $context['bulk_operation'] = [
                'action' => $request->get('bulk_action'),
                'count' => is_array($request->get('selected_ids')) ? count($request->get('selected_ids')) : 0
            ];
        }

        return $context;
    }

    /**
     * Sanitize input data for logging
     */
    private function sanitizeInput(array $input): array
    {
        foreach ($input as $key => $value) {
            if (in_array(strtolower($key), $this->excludedFields)) {
                $input[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $input[$key] = $this->sanitizeInput($value);
            } elseif (is_string($value) && strlen($value) > 1000) {
                // Truncate very long strings
                $input[$key] = substr($value, 0, 1000) . '... [TRUNCATED]';
            }
        }

        return $input;
    }

    /**
     * Log slow requests for performance monitoring
     */
    private function logSlowRequest(Request $request, float $executionTime, int $memoryUsage): void
    {
        Log::warning('Slow request detected', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time_ms' => round($executionTime, 2),
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
            'route_name' => $request->route() ? $request->route()->getName() : null
        ]);
    }

    /**
     * Add audit-related headers to response
     */
    private function addAuditHeaders(Response $response, float $executionTime): void
    {
        $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
        $response->headers->set('X-Request-ID', request()->get('request_id', Str::uuid()->toString()));
        
        if (Auth::check()) {
            $response->headers->set('X-User-ID', Auth::id());
        }
    }
}
