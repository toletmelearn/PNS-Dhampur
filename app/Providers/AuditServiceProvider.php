<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class AuditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register audit services
        $this->registerAuditServices();
        
        // Register performance monitoring services
        $this->registerPerformanceServices();
        
        // Register activity tracking services
        $this->registerActivityServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure audit settings
        $this->configureAuditSettings();
        
        // Register audit event listeners
        $this->registerAuditEventListeners();
        
        // Configure database query logging
        $this->configureDatabaseQueryLogging();
        
        // Register performance monitoring
        $this->registerPerformanceMonitoring();
        
        // Configure activity tracking
        $this->configureActivityTracking();
    }

    /**
     * Register audit services
     */
    protected function registerAuditServices(): void
    {
        // Audit Logger Service
        $this->app->singleton('audit.logger', function ($app) {
            return new \App\Services\AuditLogger([
                'enabled' => config('audit.enabled', true),
                'log_channel' => config('audit.log_channel', 'audit'),
                'database_logging' => config('audit.database_logging', true),
                'file_logging' => config('audit.file_logging', true),
                'retention_days' => config('audit.retention_days', 90),
            ]);
        });

        // Audit Trail Service
        $this->app->singleton('audit.trail', function ($app) {
            return new \App\Services\AuditTrail($app['audit.logger']);
        });

        // Compliance Logger
        $this->app->singleton('audit.compliance', function ($app) {
            return new \App\Services\ComplianceLogger($app['audit.logger']);
        });
    }

    /**
     * Register performance monitoring services
     */
    protected function registerPerformanceServices(): void
    {
        // Performance Monitor
        $this->app->singleton('performance.monitor', function ($app) {
            return new \App\Services\PerformanceMonitor([
                'enabled' => config('performance.monitoring.enabled', true),
                'slow_query_threshold' => config('performance.slow_query_threshold', 1000),
                'memory_threshold' => config('performance.memory_threshold', 128),
                'response_time_threshold' => config('performance.response_time_threshold', 2000),
            ]);
        });

        // Query Analyzer
        $this->app->singleton('performance.query_analyzer', function ($app) {
            return new \App\Services\QueryAnalyzer($app['performance.monitor']);
        });

        // Memory Monitor
        $this->app->singleton('performance.memory', function ($app) {
            return new \App\Services\MemoryMonitor($app['performance.monitor']);
        });
    }

    /**
     * Register activity tracking services
     */
    protected function registerActivityServices(): void
    {
        // Activity Tracker
        $this->app->singleton('activity.tracker', function ($app) {
            return new \App\Services\ActivityTracker([
                'enabled' => config('activity.tracking.enabled', true),
                'track_anonymous' => config('activity.track_anonymous', false),
                'track_api_calls' => config('activity.track_api_calls', true),
                'track_file_operations' => config('activity.track_file_operations', true),
                'batch_size' => config('activity.batch_size', 100),
            ]);
        });

        // User Activity Logger
        $this->app->singleton('activity.user_logger', function ($app) {
            return new \App\Services\UserActivityLogger($app['activity.tracker']);
        });

        // System Activity Logger
        $this->app->singleton('activity.system_logger', function ($app) {
            return new \App\Services\SystemActivityLogger($app['activity.tracker']);
        });
    }

    /**
     * Configure audit settings
     */
    protected function configureAuditSettings(): void
    {
        // Set audit configuration
        Config::set([
            'audit.enabled' => env('AUDIT_ENABLED', true),
            'audit.log_channel' => env('AUDIT_LOG_CHANNEL', 'audit'),
            'audit.database_logging' => env('AUDIT_DATABASE_LOGGING', true),
            'audit.file_logging' => env('AUDIT_FILE_LOGGING', true),
            'audit.retention_days' => env('AUDIT_RETENTION_DAYS', 90),
            'audit.events' => [
                'user_login',
                'user_logout',
                'user_created',
                'user_updated',
                'user_deleted',
                'password_changed',
                'role_assigned',
                'permission_granted',
                'data_exported',
                'file_uploaded',
                'file_downloaded',
                'sensitive_data_accessed',
                'system_configuration_changed',
                'backup_created',
                'backup_restored',
            ],
            'audit.sensitive_fields' => [
                'password',
                'password_confirmation',
                'current_password',
                'token',
                'api_key',
                'secret',
                'private_key',
                'credit_card',
                'ssn',
                'phone',
                'email',
            ],
        ]);

        // Performance monitoring configuration
        Config::set([
            'performance.monitoring.enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
            'performance.slow_query_threshold' => env('SLOW_QUERY_THRESHOLD', 1000),
            'performance.memory_threshold' => env('MEMORY_THRESHOLD', 128),
            'performance.response_time_threshold' => env('RESPONSE_TIME_THRESHOLD', 2000),
            'performance.log_channel' => env('PERFORMANCE_LOG_CHANNEL', 'performance'),
        ]);

        // Activity tracking configuration
        Config::set([
            'activity.tracking.enabled' => env('ACTIVITY_TRACKING_ENABLED', true),
            'activity.track_anonymous' => env('ACTIVITY_TRACK_ANONYMOUS', false),
            'activity.track_api_calls' => env('ACTIVITY_TRACK_API_CALLS', true),
            'activity.track_file_operations' => env('ACTIVITY_TRACK_FILE_OPERATIONS', true),
            'activity.batch_size' => env('ACTIVITY_BATCH_SIZE', 100),
            'activity.log_channel' => env('ACTIVITY_LOG_CHANNEL', 'activity'),
        ]);
    }

    /**
     * Register audit event listeners
     */
    protected function registerAuditEventListeners(): void
    {
        // User authentication events
        Event::listen('auth.login', function ($event) {
            $this->logAuditEvent('user_login', [
                'user_id' => $event->user->id,
                'user_email' => $event->user->email,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now(),
            ]);
        });

        Event::listen('auth.logout', function ($event) {
            $this->logAuditEvent('user_logout', [
                'user_id' => $event->user->id,
                'user_email' => $event->user->email,
                'ip_address' => request()->ip(),
                'timestamp' => now(),
            ]);
        });

        Event::listen('auth.failed', function ($event) {
            $this->logAuditEvent('login_failed', [
                'email' => $event->credentials['email'] ?? 'unknown',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now(),
            ]);
        });

        // Model events
        Event::listen('eloquent.created: *', function ($eventName, $data) {
            $this->logModelEvent('created', $eventName, $data);
        });

        Event::listen('eloquent.updated: *', function ($eventName, $data) {
            $this->logModelEvent('updated', $eventName, $data);
        });

        Event::listen('eloquent.deleted: *', function ($eventName, $data) {
            $this->logModelEvent('deleted', $eventName, $data);
        });

        // File operation events
        Event::listen('file.uploaded', function ($event) {
            $this->logAuditEvent('file_uploaded', [
                'file_name' => $event->fileName,
                'file_size' => $event->fileSize,
                'file_type' => $event->fileType,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'timestamp' => now(),
            ]);
        });

        Event::listen('file.downloaded', function ($event) {
            $this->logAuditEvent('file_downloaded', [
                'file_name' => $event->fileName,
                'file_path' => $event->filePath,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'timestamp' => now(),
            ]);
        });

        // System events
        Event::listen('system.backup.created', function ($event) {
            $this->logAuditEvent('backup_created', [
                'backup_name' => $event->backupName,
                'backup_size' => $event->backupSize,
                'user_id' => auth()->id(),
                'timestamp' => now(),
            ]);
        });

        Event::listen('system.configuration.changed', function ($event) {
            $this->logAuditEvent('system_configuration_changed', [
                'configuration_key' => $event->configKey,
                'old_value' => $event->oldValue,
                'new_value' => $event->newValue,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'timestamp' => now(),
            ]);
        });
    }

    /**
     * Configure database query logging
     */
    protected function configureDatabaseQueryLogging(): void
    {
        if (config('performance.monitoring.enabled')) {
            DB::listen(function ($query) {
                $threshold = config('performance.slow_query_threshold', 1000);
                
                if ($query->time > $threshold) {
                    $this->logSlowQuery($query);
                }
                
                // Log all queries in debug mode
                if (config('app.debug')) {
                    $this->logDatabaseQuery($query);
                }
            });
        }
    }

    /**
     * Register performance monitoring
     */
    protected function registerPerformanceMonitoring(): void
    {
        if (config('performance.monitoring.enabled')) {
            // Monitor memory usage
            register_tick_function(function () {
                $memoryUsage = memory_get_usage(true);
                $threshold = config('performance.memory_threshold', 128) * 1024 * 1024;
                
                if ($memoryUsage > $threshold) {
                    $this->logPerformanceIssue('high_memory_usage', [
                        'memory_usage' => $memoryUsage,
                        'threshold' => $threshold,
                        'route' => request()->route()?->getName(),
                        'timestamp' => now(),
                    ]);
                }
            });
        }
    }

    /**
     * Configure activity tracking
     */
    protected function configureActivityTracking(): void
    {
        if (config('activity.tracking.enabled')) {
            // Track page views
            Event::listen('page.viewed', function ($event) {
                $this->logActivity('page_viewed', [
                    'url' => $event->url,
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'timestamp' => now(),
                ]);
            });

            // Track API calls
            if (config('activity.track_api_calls')) {
                Event::listen('api.called', function ($event) {
                    $this->logActivity('api_called', [
                        'endpoint' => $event->endpoint,
                        'method' => $event->method,
                        'user_id' => auth()->id(),
                        'ip_address' => request()->ip(),
                        'response_code' => $event->responseCode,
                        'timestamp' => now(),
                    ]);
                });
            }
        }
    }

    /**
     * Log audit event
     */
    protected function logAuditEvent(string $event, array $data): void
    {
        if ($this->app->bound('audit.logger')) {
            $this->app['audit.logger']->log($event, $data);
        }

        // Also log to Laravel's log system
        Log::channel('audit')->info("Audit Event: {$event}", $data);
    }

    /**
     * Log model event
     */
    protected function logModelEvent(string $action, string $eventName, array $data): void
    {
        $model = $data[0] ?? null;
        
        if (!$model) {
            return;
        }

        $modelClass = get_class($model);
        $modelName = class_basename($modelClass);

        $auditData = [
            'action' => $action,
            'model' => $modelName,
            'model_id' => $model->getKey(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'timestamp' => now(),
        ];

        // Add changed attributes for updates
        if ($action === 'updated' && method_exists($model, 'getDirty')) {
            $auditData['changes'] = $this->sanitizeChanges($model->getDirty());
            $auditData['original'] = $this->sanitizeChanges($model->getOriginal());
        }

        $this->logAuditEvent("model_{$action}", $auditData);
    }

    /**
     * Log slow query
     */
    protected function logSlowQuery($query): void
    {
        Log::channel('performance')->warning('Slow Query Detected', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'connection' => $query->connectionName,
            'route' => request()->route()?->getName(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log database query
     */
    protected function logDatabaseQuery($query): void
    {
        Log::channel('performance')->debug('Database Query', [
            'sql' => $query->sql,
            'bindings' => $query->bindings,
            'time' => $query->time,
            'connection' => $query->connectionName,
        ]);
    }

    /**
     * Log performance issue
     */
    protected function logPerformanceIssue(string $issue, array $data): void
    {
        Log::channel('performance')->warning("Performance Issue: {$issue}", $data);
        
        if ($this->app->bound('performance.monitor')) {
            $this->app['performance.monitor']->logIssue($issue, $data);
        }
    }

    /**
     * Log activity
     */
    protected function logActivity(string $activity, array $data): void
    {
        if ($this->app->bound('activity.tracker')) {
            $this->app['activity.tracker']->log($activity, $data);
        }

        Log::channel('activity')->info("Activity: {$activity}", $data);
    }

    /**
     * Sanitize changes to remove sensitive data
     */
    protected function sanitizeChanges(array $changes): array
    {
        $sensitiveFields = config('audit.sensitive_fields', []);
        
        foreach ($sensitiveFields as $field) {
            if (isset($changes[$field])) {
                $changes[$field] = '[REDACTED]';
            }
        }
        
        return $changes;
    }
}