<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LoggingService
{
    /**
     * Log security-related events
     */
    public static function security(string $message, array $context = [], string $level = 'info'): void
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => Carbon::now()->toISOString(),
            'session_id' => session()->getId(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('security')->{$level}($message, $context);
    }

    /**
     * Log authentication events
     */
    public static function auth(string $event, array $context = []): void
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'timestamp' => Carbon::now()->toISOString(),
            'session_id' => session()->getId(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('auth')->info("AUTH_EVENT: {$event}", $context);
    }

    /**
     * Log performance metrics
     */
    public static function performance(string $metric, float $value, array $context = []): void
    {
        $context = array_merge([
            'metric' => $metric,
            'value' => $value,
            'unit' => $context['unit'] ?? 'ms',
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('performance')->info("PERFORMANCE: {$metric}", $context);
    }

    /**
     * Log database queries
     */
    public static function database(string $query, float $time, array $bindings = []): void
    {
        $context = [
            'query' => $query,
            'execution_time' => $time,
            'bindings' => $bindings,
            'url' => Request::fullUrl(),
            'user_id' => Auth::id(),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ];

        $level = $time > config('logging.performance.slow_query_threshold', 1000) ? 'warning' : 'debug';
        
        Log::channel('database')->{$level}("DATABASE_QUERY", $context);
    }

    /**
     * Log API requests and responses
     */
    public static function api(string $endpoint, int $statusCode, array $context = []): void
    {
        $context = array_merge([
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'method' => Request::method(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => Auth::id(),
            'response_time' => $context['response_time'] ?? null,
            'request_size' => strlen(Request::getContent()),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ], $context);

        $level = $statusCode >= 400 ? 'warning' : 'info';
        
        Log::channel('api')->{$level}("API_REQUEST: {$endpoint}", $context);
    }

    /**
     * Log errors with full context
     */
    public static function error(\Throwable $exception, array $context = []): void
    {
        $context = array_merge([
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'request_data' => Request::except(['password', 'password_confirmation', '_token']),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('errors')->error("APPLICATION_ERROR: {$exception->getMessage()}", $context);
    }

    /**
     * Log user actions for audit trail
     */
    public static function audit(string $action, string $resource, array $context = []): void
    {
        $context = array_merge([
            'action' => $action,
            'resource' => $resource,
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'user_role' => Auth::user()?->role,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => Carbon::now()->toISOString(),
            'session_id' => session()->getId(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('security')->info("AUDIT: {$action} on {$resource}", $context);
    }

    /**
     * Log validation failures
     */
    public static function validation(string $form, array $errors, array $context = []): void
    {
        $context = array_merge([
            'form' => $form,
            'validation_errors' => $errors,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('security')->warning("VALIDATION_FAILURE: {$form}", $context);
    }

    /**
     * Log file operations
     */
    public static function fileOperation(string $operation, string $filename, array $context = []): void
    {
        $context = array_merge([
            'operation' => $operation,
            'filename' => $filename,
            'file_size' => $context['file_size'] ?? null,
            'file_type' => $context['file_type'] ?? null,
            'user_id' => Auth::id(),
            'ip_address' => Request::ip(),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('security')->info("FILE_OPERATION: {$operation} - {$filename}", $context);
    }

    /**
     * Log rate limiting events
     */
    public static function rateLimitHit(string $key, int $attempts, array $context = []): void
    {
        $context = array_merge([
            'rate_limit_key' => $key,
            'attempts' => $attempts,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'user_id' => Auth::id(),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('security')->warning("RATE_LIMIT_HIT: {$key}", $context);
    }

    /**
     * Log suspicious activity
     */
    public static function suspicious(string $activity, string $reason, array $context = []): void
    {
        $context = array_merge([
            'activity' => $activity,
            'reason' => $reason,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'user_id' => Auth::id(),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
            'severity' => $context['severity'] ?? 'medium',
        ], $context);

        Log::channel('security')->warning("SUSPICIOUS_ACTIVITY: {$activity}", $context);
    }

    /**
     * Get or generate request ID for tracking
     */
    private static function getRequestId(): string
    {
        // Use the request() helper to interact with the current request instance
        if (!request()->hasHeader('X-Request-ID')) {
            $requestId = Str::uuid()->toString();
            // Set a generated ID on the request headers for downstream logging
            request()->headers->set('X-Request-ID', $requestId);
            return $requestId;
        }

        return request()->header('X-Request-ID');
    }

    /**
     * Log system events
     */
    public static function system(string $event, array $context = []): void
    {
        $context = array_merge([
            'event' => $event,
            'timestamp' => Carbon::now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::info("SYSTEM_EVENT: {$event}", $context);
    }

    /**
     * Log configuration changes
     */
    public static function configChange(string $key, $oldValue, $newValue, array $context = []): void
    {
        $context = array_merge([
            'config_key' => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'ip_address' => Request::ip(),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('security')->warning("CONFIG_CHANGE: {$key}", $context);
    }

    /**
     * Log data export events
     */
    public static function dataExport(string $type, int $recordCount, array $context = []): void
    {
        $context = array_merge([
            'export_type' => $type,
            'record_count' => $recordCount,
            'user_id' => Auth::id(),
            'user_email' => Auth::user()?->email,
            'ip_address' => Request::ip(),
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ], $context);

        Log::channel('security')->info("DATA_EXPORT: {$type}", $context);
    }

    /**
     * Log backup operations
     */
    public static function backup(string $operation, string $status, array $context = []): void
    {
        $context = array_merge([
            'operation' => $operation,
            'status' => $status,
            'timestamp' => Carbon::now()->toISOString(),
            'request_id' => self::getRequestId(),
        ], $context);

        $level = $status === 'success' ? 'info' : 'error';
        
        Log::channel('system')->{$level}("BACKUP: {$operation} - {$status}", $context);
    }
}