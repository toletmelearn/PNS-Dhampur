<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\UserActivity;
use Carbon\Carbon;

class UserActivityMiddleware
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
        $startTime = microtime(true);
        
        // Process the request
        $response = $next($request);
        
        // Calculate response time
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Only log activity for authenticated users
        if (auth()->check()) {
            try {
                $this->logUserActivity($request, $response, $responseTime);
            } catch (\Exception $e) {
                // Log error but don't break the request
                Log::error('User activity logging failed: ' . $e->getMessage());
            }
        }
        
        return $response;
    }

    /**
     * Log user activity
     */
    private function logUserActivity($request, $response, $responseTime)
    {
        // Skip logging for certain routes to avoid noise
        if ($this->shouldSkipLogging($request)) {
            return;
        }

        $activityType = $this->determineActivityType($request, $response);
        $description = $this->generateDescription($request, $activityType);
        
        // Extract subject information if available
        $subjectType = null;
        $subjectId = null;
        $this->extractSubjectInfo($request, $subjectType, $subjectId);

        UserActivity::create([
            'user_id' => auth()->id(),
            'activity_type' => $activityType,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $this->getActivityProperties($request, $response),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'request_data' => $this->getFilteredRequestData($request),
            'response_time' => $responseTime,
            'status_code' => $response->getStatusCode(),
            'performed_at' => Carbon::now(),
        ]);
    }

    /**
     * Determine if we should skip logging for this request
     */
    private function shouldSkipLogging($request)
    {
        $skipRoutes = [
            'api/health',
            'api/ping',
            'livewire',
            '_ignition',
            'telescope',
            'horizon',
            'debugbar',
        ];

        $path = $request->path();
        
        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($path, $skipRoute)) {
                return true;
            }
        }

        // Skip asset requests
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Determine the activity type based on the request
     */
    private function determineActivityType($request, $response)
    {
        $method = $request->method();
        $path = $request->path();
        
        // Check for specific activity types
        if (str_contains($path, 'login')) {
            return 'login';
        }
        
        if (str_contains($path, 'logout')) {
            return 'logout';
        }
        
        if (str_contains($path, 'download')) {
            return 'download';
        }
        
        if (str_contains($path, 'upload')) {
            return 'upload';
        }
        
        // Determine by HTTP method
        switch ($method) {
            case 'GET':
                return 'view';
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return 'unknown';
        }
    }

    /**
     * Generate a human-readable description
     */
    private function generateDescription($request, $activityType)
    {
        $path = $request->path();
        $method = $request->method();
        
        // Generate description based on activity type and path
        $descriptions = [
            'login' => 'User logged in',
            'logout' => 'User logged out',
            'view' => "Viewed {$path}",
            'create' => "Created new record via {$path}",
            'update' => "Updated record via {$path}",
            'delete' => "Deleted record via {$path}",
            'download' => "Downloaded file from {$path}",
            'upload' => "Uploaded file to {$path}",
        ];
        
        return $descriptions[$activityType] ?? "{$method} request to {$path}";
    }

    /**
     * Extract subject information from the request
     */
    private function extractSubjectInfo($request, &$subjectType, &$subjectId)
    {
        $path = $request->path();
        
        // Try to extract model information from route parameters
        $routeParameters = $request->route() ? $request->route()->parameters() : [];
        
        foreach ($routeParameters as $key => $value) {
            if (is_numeric($value)) {
                // Try to determine the model type from the parameter name
                $modelMappings = [
                    'student' => 'App\Models\Student',
                    'teacher' => 'App\Models\Teacher',
                    'user' => 'App\Models\User',
                    'class' => 'App\Models\SchoolClass',
                    'subject' => 'App\Models\Subject',
                    'exam' => 'App\Models\Exam',
                    'attendance' => 'App\Models\Attendance',
                    'fee' => 'App\Models\Fee',
                    'holiday' => 'App\Models\Holiday',
                    'setting' => 'App\Models\SystemSetting',
                    'template' => 'App\Models\NotificationTemplate',
                ];
                
                if (isset($modelMappings[$key])) {
                    $subjectType = $modelMappings[$key];
                    $subjectId = $value;
                    break;
                }
            }
        }
    }

    /**
     * Get activity properties
     */
    private function getActivityProperties($request, $response)
    {
        return [
            'route_name' => $request->route() ? $request->route()->getName() : null,
            'controller' => $request->route() ? $request->route()->getActionName() : null,
            'response_size' => strlen($response->getContent()),
            'request_size' => strlen($request->getContent()),
            'referer' => $request->header('referer'),
            'is_ajax' => $request->ajax(),
            'is_json' => $request->expectsJson(),
        ];
    }

    /**
     * Get filtered request data (excluding sensitive information)
     */
    private function getFilteredRequestData($request)
    {
        $data = $request->all();
        
        // Remove sensitive fields
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_token',
            '_token',
            'csrf_token',
            'credit_card',
            'ssn',
            'social_security',
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }
        
        // Limit the size of the data to prevent huge logs
        $jsonData = json_encode($data);
        if (strlen($jsonData) > 5000) {
            return ['message' => 'Request data too large to log'];
        }
        
        return $data;
    }
}
