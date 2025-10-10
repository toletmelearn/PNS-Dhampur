<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Exception;
use Throwable;
use Illuminate\Http\Request;
use App\Models\ErrorLog;
use App\Models\User;
use Carbon\Carbon;

class ComprehensiveErrorHandlingService
{
    /**
     * Critical error types that require immediate attention
     */
    const CRITICAL_ERROR_TYPES = [
        'database_connection_failed',
        'security_breach_detected',
        'payment_processing_failed',
        'data_corruption_detected',
        'system_overload',
        'authentication_system_failure'
    ];

    /**
     * Error severity levels
     */
    const SEVERITY_LEVELS = [
        'emergency' => 1,
        'alert' => 2,
        'critical' => 3,
        'error' => 4,
        'warning' => 5,
        'notice' => 6,
        'info' => 7,
        'debug' => 8
    ];

    /**
     * Handle and log comprehensive error information
     */
    public function handleError(Throwable $exception, Request $request = null, array $context = []): array
    {
        try {
            $errorData = $this->extractErrorData($exception, $request, $context);
            
            // Log to database
            $errorLog = $this->logToDatabase($errorData);
            
            // Log to file system
            $this->logToFile($errorData);
            
            // Check if critical error requires immediate notification
            if ($this->isCriticalError($errorData)) {
                $this->handleCriticalError($errorData, $errorLog);
            }
            
            // Update error statistics
            $this->updateErrorStatistics($errorData);
            
            return [
                'error_id' => $errorLog->id ?? null,
                'severity' => $errorData['severity'],
                'user_message' => $this->getUserFriendlyMessage($errorData),
                'should_retry' => $this->shouldRetry($errorData),
                'retry_after' => $this->getRetryDelay($errorData)
            ];
            
        } catch (Exception $e) {
            // Fallback logging if error handling fails
            Log::emergency('Error handling system failed', [
                'original_error' => $exception->getMessage(),
                'handler_error' => $e->getMessage(),
                'timestamp' => now()
            ]);
            
            return [
                'error_id' => null,
                'severity' => 'critical',
                'user_message' => 'A system error occurred. Please try again later.',
                'should_retry' => false,
                'retry_after' => null
            ];
        }
    }

    /**
     * Extract comprehensive error data
     */
    private function extractErrorData(Throwable $exception, ?Request $request, array $context): array
    {
        $errorData = [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'severity' => $this->determineSeverity($exception),
            'error_type' => $this->determineErrorType($exception),
            'timestamp' => now(),
            'context' => $context
        ];

        if ($request) {
            $errorData = array_merge($errorData, [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
                'session_id' => $request->session()->getId(),
                'request_data' => $this->sanitizeRequestData($request->all()),
                'headers' => $this->sanitizeHeaders($request->headers->all())
            ]);
        }

        return $errorData;
    }

    /**
     * Log error to database
     */
    private function logToDatabase(array $errorData): ?ErrorLog
    {
        try {
            return ErrorLog::create([
                'level' => $errorData['severity'],
                'message' => $errorData['message'],
                'context' => json_encode($errorData['context']),
                'file' => $errorData['file'],
                'line' => $errorData['line'],
                'trace' => $errorData['trace'],
                'url' => $errorData['url'] ?? null,
                'method' => $errorData['method'] ?? null,
                'ip_address' => $errorData['ip_address'] ?? null,
                'user_agent' => $errorData['user_agent'] ?? null,
                'user_id' => $errorData['user_id'] ?? null,
                'session_id' => $errorData['session_id'] ?? null,
                'request_data' => $errorData['request_data'] ?? null,
                'exception_class' => $errorData['exception_class'],
                'is_resolved' => false
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log error to database', [
                'error' => $e->getMessage(),
                'original_error' => $errorData['message']
            ]);
            return null;
        }
    }

    /**
     * Log error to file system with structured format
     */
    private function logToFile(array $errorData): void
    {
        $logLevel = $errorData['severity'];
        $logChannel = $this->getLogChannel($errorData);

        Log::channel($logChannel)->log($logLevel, $errorData['message'], [
            'exception' => $errorData['exception_class'],
            'file' => $errorData['file'],
            'line' => $errorData['line'],
            'url' => $errorData['url'] ?? null,
            'user_id' => $errorData['user_id'] ?? null,
            'ip' => $errorData['ip_address'] ?? null,
            'context' => $errorData['context'],
            'timestamp' => $errorData['timestamp']
        ]);
    }

    /**
     * Handle critical errors with immediate notifications
     */
    private function handleCriticalError(array $errorData, ?ErrorLog $errorLog): void
    {
        // Prevent notification spam
        $cacheKey = 'critical_error_notified_' . md5($errorData['message'] . $errorData['file'] . $errorData['line']);
        
        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(30));

        // Log to emergency channel
        Log::channel('emergency')->emergency('Critical error detected', $errorData);

        // Send notifications to administrators
        $this->notifyAdministrators($errorData, $errorLog);

        // Create system alert
        $this->createSystemAlert($errorData);
    }

    /**
     * Notify system administrators of critical errors
     */
    private function notifyAdministrators(array $errorData, ?ErrorLog $errorLog): void
    {
        try {
            $admins = User::where('role', 'admin')->get();
            
            foreach ($admins as $admin) {
                // Send email notification (implement based on your mail setup)
                // Mail::to($admin->email)->send(new CriticalErrorNotification($errorData, $errorLog));
            }
        } catch (Exception $e) {
            Log::error('Failed to notify administrators', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create system alert for critical errors
     */
    private function createSystemAlert(array $errorData): void
    {
        try {
            DB::table('system_alerts')->insert([
                'type' => 'critical_error',
                'title' => 'Critical System Error Detected',
                'message' => $errorData['message'],
                'severity' => 'critical',
                'data' => json_encode($errorData),
                'is_resolved' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create system alert', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Determine error severity based on exception type and context
     */
    private function determineSeverity(Throwable $exception): string
    {
        $exceptionClass = get_class($exception);
        
        // Map exception types to severity levels
        $severityMap = [
            'Illuminate\Database\QueryException' => 'error',
            'Illuminate\Auth\AuthenticationException' => 'warning',
            'Illuminate\Auth\Access\AuthorizationException' => 'warning',
            'Illuminate\Session\TokenMismatchException' => 'warning',
            'Illuminate\Validation\ValidationException' => 'notice',
            'Symfony\Component\HttpKernel\Exception\NotFoundHttpException' => 'info',
            'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException' => 'warning',
            'ErrorException' => 'error',
            'ParseError' => 'critical',
            'TypeError' => 'error',
            'OutOfMemoryError' => 'critical'
        ];

        return $severityMap[$exceptionClass] ?? 'error';
    }

    /**
     * Determine error type for categorization
     */
    private function determineErrorType(Throwable $exception): string
    {
        $message = strtolower($exception->getMessage());
        
        if (strpos($message, 'database') !== false || strpos($message, 'connection') !== false) {
            return 'database_error';
        }
        
        if (strpos($message, 'permission') !== false || strpos($message, 'unauthorized') !== false) {
            return 'authorization_error';
        }
        
        if (strpos($message, 'validation') !== false) {
            return 'validation_error';
        }
        
        if (strpos($message, 'file') !== false || strpos($message, 'upload') !== false) {
            return 'file_error';
        }
        
        return 'general_error';
    }

    /**
     * Check if error is critical and requires immediate attention
     */
    private function isCriticalError(array $errorData): bool
    {
        return in_array($errorData['severity'], ['emergency', 'alert', 'critical']) ||
               in_array($errorData['error_type'], self::CRITICAL_ERROR_TYPES);
    }

    /**
     * Get appropriate log channel based on error data
     */
    private function getLogChannel(array $errorData): string
    {
        if (in_array($errorData['severity'], ['emergency', 'alert', 'critical'])) {
            return 'production_stack';
        }
        
        if ($errorData['error_type'] === 'authorization_error') {
            return 'security';
        }
        
        return 'daily';
    }

    /**
     * Get user-friendly error message
     */
    private function getUserFriendlyMessage(array $errorData): string
    {
        $messages = [
            'database_error' => 'We are experiencing technical difficulties. Please try again in a few moments.',
            'authorization_error' => 'You do not have permission to perform this action.',
            'validation_error' => 'Please check your input and try again.',
            'file_error' => 'There was an issue processing your file. Please try again.',
            'general_error' => 'An unexpected error occurred. Please try again later.'
        ];

        return $messages[$errorData['error_type']] ?? $messages['general_error'];
    }

    /**
     * Determine if operation should be retried
     */
    private function shouldRetry(array $errorData): bool
    {
        $retryableTypes = ['database_error', 'file_error'];
        $retryableSeverities = ['warning', 'notice', 'info'];
        
        return in_array($errorData['error_type'], $retryableTypes) ||
               in_array($errorData['severity'], $retryableSeverities);
    }

    /**
     * Get retry delay in seconds
     */
    private function getRetryDelay(array $errorData): ?int
    {
        if (!$this->shouldRetry($errorData)) {
            return null;
        }
        
        $delays = [
            'database_error' => 30,
            'file_error' => 10,
            'general_error' => 60
        ];
        
        return $delays[$errorData['error_type']] ?? 30;
    }

    /**
     * Sanitize request data for logging
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', '_token', 'api_key', 
            'secret', 'private_key', 'credit_card', 'ssn'
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        return $data;
    }

    /**
     * Sanitize headers for logging
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'x-api-key', 'cookie'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[REDACTED]'];
            }
        }
        
        return $headers;
    }

    /**
     * Update error statistics for monitoring
     */
    private function updateErrorStatistics(array $errorData): void
    {
        try {
            $today = Carbon::today();
            $cacheKey = "error_stats_{$today->format('Y-m-d')}";
            
            $stats = Cache::get($cacheKey, [
                'total_errors' => 0,
                'critical_errors' => 0,
                'by_type' => [],
                'by_severity' => []
            ]);
            
            $stats['total_errors']++;
            
            if (in_array($errorData['severity'], ['emergency', 'alert', 'critical'])) {
                $stats['critical_errors']++;
            }
            
            $stats['by_type'][$errorData['error_type']] = 
                ($stats['by_type'][$errorData['error_type']] ?? 0) + 1;
                
            $stats['by_severity'][$errorData['severity']] = 
                ($stats['by_severity'][$errorData['severity']] ?? 0) + 1;
            
            Cache::put($cacheKey, $stats, now()->addDay());
            
        } catch (Exception $e) {
            Log::error('Failed to update error statistics', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get error statistics for dashboard
     */
    public function getErrorStatistics(int $days = 7): array
    {
        $stats = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->subDays($i);
            $cacheKey = "error_stats_{$date->format('Y-m-d')}";
            $dayStats = Cache::get($cacheKey, [
                'total_errors' => 0,
                'critical_errors' => 0,
                'by_type' => [],
                'by_severity' => []
            ]);
            
            $stats[$date->format('Y-m-d')] = $dayStats;
        }
        
        return $stats;
    }

    /**
     * Handle security events from middleware and other security components
     */
    public function handleSecurityEvent(string $event, array $context): void
    {
        try {
            // Determine severity based on event type
            $severity = $this->getSecurityEventSeverity($event);
            
            // Log the security event
            $this->logSecurityEvent($event, $context, $severity);
            
            // Store in database for audit trail
            $this->storeSecurityEvent($event, $context, $severity);
            
            // Send notifications for critical security events
            if ($severity >= 4) {
                $this->notifySecurityTeam($event, $context, $severity);
            }
            
            // Create system alert for high-severity events
            if ($severity >= 3) {
                $this->createSecurityAlert($event, $context, $severity);
            }
            
            // Update security metrics
            $this->updateSecurityMetrics($event, $context);
            
        } catch (Exception $e) {
            // Fallback logging if security event handling fails
            Log::emergency('Security event handling failed', [
                'original_event' => $event,
                'original_context' => $context,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get severity level for security events
     */
    protected function getSecurityEventSeverity(string $event): int
    {
        $severityMap = [
            'session_hijacking_detected' => 5, // Critical
            'authentication_failed' => 3,      // High
            'access_denied' => 2,              // Medium
            'attendance_access_denied' => 2,   // Medium
            'permission_denied' => 2,          // Medium
            'admin_override_used' => 1,        // Low
            'access_granted' => 1,             // Low
            'session_timeout' => 1,            // Low
            'admin_student_route_blocked' => 2 // Medium
        ];

        return $severityMap[$event] ?? 2; // Default to medium
    }

    /**
     * Log security event with appropriate level
     */
    protected function logSecurityEvent(string $event, array $context, int $severity): void
    {
        $logLevel = $this->getLogLevelFromSeverity($severity);
        
        $logContext = array_merge($context, [
            'event_type' => 'security',
            'event_name' => $event,
            'severity' => $severity,
            'timestamp' => now()->toISOString(),
            'server' => gethostname(),
            'application' => config('app.name', 'Laravel')
        ]);

        // Log to security channel
        Log::channel('security')->{$logLevel}("Security Event: {$event}", $logContext);
        
        // Also log critical events to audit channel
        if ($severity >= 4) {
            Log::channel('audit')->{$logLevel}("Critical Security Event: {$event}", $logContext);
        }
    }

    /**
     * Store security event in database
     */
    protected function storeSecurityEvent(string $event, array $context, int $severity): void
    {
        try {
            DB::table('security_events')->insert([
                'event_type' => $event,
                'severity' => $severity,
                'context' => json_encode($context),
                'ip_address' => $context['ip'] ?? null,
                'user_id' => $context['user_id'] ?? null,
                'user_agent' => isset($context['user_agent']) ? substr($context['user_agent'], 0, 500) : null,
                'route' => $context['route'] ?? null,
                'method' => $context['method'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (Exception $e) {
            // Log database storage failure
            Log::error('Failed to store security event in database', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify security team of critical events
     */
    protected function notifySecurityTeam(string $event, array $context, int $severity): void
    {
        try {
            $notificationData = [
                'event' => $event,
                'severity' => $severity,
                'context' => $context,
                'timestamp' => now()->toISOString(),
                'server' => gethostname()
            ];

            // Send email notification
            $this->sendSecurityNotification($notificationData);
            
            // Send Slack notification if configured
            if (config('logging.channels.slack.url')) {
                $this->sendSlackSecurityAlert($notificationData);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to send security notification', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create system alert for security events
     */
    protected function createSecurityAlert(string $event, array $context, int $severity): void
    {
        try {
            $alertKey = "security_alert:{$event}:" . date('Y-m-d-H');
            $alertCount = Cache::increment($alertKey, 1);
            
            if ($alertCount === 1) {
                Cache::put($alertKey, 1, 3600); // 1 hour expiry
            }
            
            // Create alert if threshold exceeded
            if ($alertCount >= $this->getAlertThreshold($event)) {
                $this->triggerSecurityAlert($event, $context, $alertCount);
            }
            
        } catch (Exception $e) {
            Log::error('Failed to create security alert', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update security metrics
     */
    protected function updateSecurityMetrics(string $event, array $context): void
    {
        try {
            $metricsKey = "security_metrics:" . date('Y-m-d');
            $metrics = Cache::get($metricsKey, []);
            
            // Update event counters
            $metrics['events'][$event] = ($metrics['events'][$event] ?? 0) + 1;
            $metrics['total_events'] = ($metrics['total_events'] ?? 0) + 1;
            
            // Update IP-based metrics
            if (isset($context['ip'])) {
                $metrics['ips'][$context['ip']] = ($metrics['ips'][$context['ip']] ?? 0) + 1;
            }
            
            // Update user-based metrics
            if (isset($context['user_id'])) {
                $metrics['users'][$context['user_id']] = ($metrics['users'][$context['user_id']] ?? 0) + 1;
            }
            
            Cache::put($metricsKey, $metrics, 86400); // 24 hours
            
        } catch (Exception $e) {
            Log::error('Failed to update security metrics', [
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get alert threshold for different event types
     */
    protected function getAlertThreshold(string $event): int
    {
        $thresholds = [
            'session_hijacking_detected' => 1,  // Immediate alert
            'authentication_failed' => 10,      // 10 failures per hour
            'access_denied' => 20,              // 20 denials per hour
            'attendance_access_denied' => 15,   // 15 denials per hour
            'permission_denied' => 25,          // 25 denials per hour
        ];

        return $thresholds[$event] ?? 50; // Default threshold
    }

    /**
     * Trigger security alert
     */
    protected function triggerSecurityAlert(string $event, array $context, int $count): void
    {
        $alertData = [
            'type' => 'security_threshold_exceeded',
            'event' => $event,
            'count' => $count,
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'server' => gethostname()
        ];

        // Log the alert
        Log::channel('security')->alert("Security threshold exceeded for {$event}", $alertData);
        
        // Send immediate notification
        $this->sendSecurityNotification($alertData);
    }

    /**
     * Send security notification email
     */
    protected function sendSecurityNotification(array $data): void
    {
        // Implementation would depend on your mail configuration
        // This is a placeholder for the actual email sending logic
        Log::info('Security notification would be sent', $data);
    }

    /**
     * Send Slack security alert
     */
    protected function sendSlackSecurityAlert(array $data): void
    {
        // Implementation would depend on your Slack configuration
        // This is a placeholder for the actual Slack notification logic
        Log::info('Slack security alert would be sent', $data);
    }

    /**
     * Get log level from severity
     */
    protected function getLogLevelFromSeverity(int $severity): string
    {
        $levels = [
            1 => 'info',
            2 => 'notice',
            3 => 'warning',
            4 => 'error',
            5 => 'critical'
        ];

        return $levels[$severity] ?? 'warning';
    }

    // ... rest of existing methods ...
}