<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AuditTrail;
use App\Models\UserSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditMiddleware
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
        // Process the request
        $response = $next($request);

        // Only log for authenticated users and specific routes
        if (Auth::check() && $this->shouldLog($request)) {
            try {
                $this->logActivity($request, $response);
                $this->updateSessionActivity($request);
            } catch (\Exception $e) {
                // Log error but don't break the request
                Log::error('Audit middleware error: ' . $e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Determine if the request should be logged
     */
    private function shouldLog(Request $request): bool
    {
        $method = $request->method();
        $path = $request->path();

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

        foreach ($skipRoutes as $skipRoute) {
            if (str_contains($path, $skipRoute)) {
                return false;
            }
        }

        // Skip GET requests to assets, images, etc.
        if ($method === 'GET' && preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $path)) {
            return false;
        }

        // Log all POST, PUT, PATCH, DELETE requests
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        // Log important GET requests
        $importantRoutes = [
            'students',
            'teachers',
            'classes',
            'subjects',
            'attendance',
            'fees',
            'reports',
            'settings',
            'users',
            'audit',
            'dashboard'
        ];

        foreach ($importantRoutes as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log the activity
     */
    private function logActivity(Request $request, $response): void
    {
        $user = Auth::user();
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();

        // Determine event type based on method and path
        $event = $this->determineEvent($method, $path);

        // Get additional data
        $additionalData = [
            'method' => $method,
            'status_code' => $statusCode,
            'response_time' => defined('LARAVEL_START') ? microtime(true) - LARAVEL_START : null,
            'memory_usage' => memory_get_peak_usage(true),
            'query_count' => \DB::getQueryLog() ? count(\DB::getQueryLog()) : 0,
        ];

        // Add request data for non-GET requests
        if ($method !== 'GET') {
            $requestData = $request->except(['password', 'password_confirmation', '_token', '_method']);
            if (!empty($requestData)) {
                $additionalData['request_data'] = $requestData;
            }
        }

        // Create audit log
        AuditTrail::logActivity(
            $user,
            $event,
            null, // auditable_type
            null, // auditable_id
            [], // old_values
            [], // new_values
            $request->fullUrl(),
            $request->ip(),
            $request->userAgent(),
            ['system_activity'],
            $additionalData
        );
    }

    /**
     * Determine event type based on method and path
     */
    private function determineEvent(string $method, string $path): string
    {
        // Map common patterns to events
        $patterns = [
            // Authentication
            'login' => 'user_login',
            'logout' => 'user_logout',
            'register' => 'user_register',
            
            // CRUD operations
            'create' => 'created',
            'store' => 'created',
            'edit' => 'viewed',
            'update' => 'updated',
            'destroy' => 'deleted',
            'delete' => 'deleted',
            
            // Bulk operations
            'bulk' => 'bulk_operation',
            
            // Reports and exports
            'export' => 'exported',
            'report' => 'report_generated',
            'download' => 'downloaded',
            
            // Settings
            'settings' => 'settings_accessed',
            'config' => 'configuration_changed',
        ];

        foreach ($patterns as $pattern => $event) {
            if (str_contains($path, $pattern)) {
                return $event;
            }
        }

        // Default events based on HTTP method
        return match($method) {
            'GET' => 'viewed',
            'POST' => 'created',
            'PUT', 'PATCH' => 'updated',
            'DELETE' => 'deleted',
            default => 'accessed'
        };
    }

    /**
     * Update session activity
     */
    private function updateSessionActivity(Request $request): void
    {
        $sessionId = session()->getId();
        if ($sessionId) {
            UserSession::updateActivity($sessionId);
        }
    }
}
