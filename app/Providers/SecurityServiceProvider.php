<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register security services
        $this->registerSecurityServices();
        
        // Register audit services
        $this->registerAuditServices();
        
        // Register rate limiting services
        $this->registerRateLimitingServices();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure security settings
        $this->configureSecuritySettings();
        
        // Register security gates and policies
        $this->registerSecurityGates();
        
        // Configure audit logging
        $this->configureAuditLogging();
        
        // Configure rate limiting
        $this->configureRateLimiting();
        
        // Register security event listeners
        $this->registerSecurityEventListeners();
    }

    /**
     * Register security services
     */
    protected function registerSecurityServices(): void
    {
        // Security Manager
        $this->app->singleton('security.manager', function ($app) {
            return new \App\Services\SecurityManager($app);
        });

        // XSS Protection Service
        $this->app->singleton('security.xss', function ($app) {
            return new \App\Services\XssProtectionService();
        });

        // SQL Injection Protection Service
        $this->app->singleton('security.sql', function ($app) {
            return new \App\Services\SqlInjectionProtectionService();
        });

        // File Upload Security Service
        $this->app->singleton('security.upload', function ($app) {
            return new \App\Services\FileUploadSecurityService();
        });

        // Session Security Service
        $this->app->singleton('security.session', function ($app) {
            return new \App\Services\SessionSecurityService();
        });
    }

    /**
     * Register audit services
     */
    protected function registerAuditServices(): void
    {
        // Audit Logger
        $this->app->singleton('audit.logger', function ($app) {
            return new \App\Services\AuditLogger($app);
        });

        // Activity Tracker
        $this->app->singleton('audit.tracker', function ($app) {
            return new \App\Services\ActivityTracker($app);
        });

        // Performance Monitor
        $this->app->singleton('audit.performance', function ($app) {
            return new \App\Services\PerformanceMonitor($app);
        });
    }

    /**
     * Register rate limiting services
     */
    protected function registerRateLimitingServices(): void
    {
        // Rate Limiter
        $this->app->singleton('rate.limiter', function ($app) {
            return new \App\Services\RateLimiter($app);
        });

        // Throttle Manager
        $this->app->singleton('throttle.manager', function ($app) {
            return new \App\Services\ThrottleManager($app);
        });
    }

    /**
     * Configure security settings
     */
    protected function configureSecuritySettings(): void
    {
        // Set default string length for database fields
        Schema::defaultStringLength(191);

        // Configure session security
        Config::set([
            'session.secure' => request()->isSecure(),
            'session.http_only' => true,
            'session.same_site' => 'strict',
            'session.encrypt' => true,
        ]);

        // Configure cookie security
        Config::set([
            'session.cookie_secure' => request()->isSecure(),
            'session.cookie_httponly' => true,
            'session.cookie_samesite' => 'strict',
        ]);

        // Configure CORS settings
        Config::set([
            'cors.allowed_origins' => [
                config('app.url'),
                'http://localhost:3000', // For development
                'http://127.0.0.1:8000', // For local development
            ],
            'cors.allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'cors.allowed_headers' => ['*'],
            'cors.exposed_headers' => [],
            'cors.max_age' => 0,
            'cors.supports_credentials' => true,
        ]);
    }

    /**
     * Register security gates and policies
     */
    protected function registerSecurityGates(): void
    {
        // Module access gates
        Gate::define('access-module', function ($user, $module) {
            return $this->checkModuleAccess($user, $module);
        });

        // Admin access gate
        Gate::define('admin-access', function ($user) {
            return $user->hasRole('admin');
        });

        // Teacher access gate
        Gate::define('teacher-access', function ($user) {
            return $user->hasAnyRole(['admin', 'principal', 'teacher']);
        });

        // Student access gate
        Gate::define('student-access', function ($user) {
            return $user->hasRole('student');
        });

        // Parent access gate
        Gate::define('parent-access', function ($user) {
            return $user->hasRole('parent');
        });

        // File access gate
        Gate::define('access-file', function ($user, $fileType, $fileId) {
            return $this->checkFileAccess($user, $fileType, $fileId);
        });

        // Sensitive operation gate
        Gate::define('sensitive-operation', function ($user, $operation) {
            return $this->checkSensitiveOperation($user, $operation);
        });
    }

    /**
     * Configure audit logging
     */
    protected function configureAuditLogging(): void
    {
        // Configure audit log channels
        Config::set([
            'logging.channels.audit' => [
                'driver' => 'daily',
                'path' => storage_path('logs/audit.log'),
                'level' => 'info',
                'days' => 90,
                'permission' => 0644,
            ],
            'logging.channels.security' => [
                'driver' => 'daily',
                'path' => storage_path('logs/security.log'),
                'level' => 'warning',
                'days' => 365,
                'permission' => 0644,
            ],
            'logging.channels.performance' => [
                'driver' => 'daily',
                'path' => storage_path('logs/performance.log'),
                'level' => 'info',
                'days' => 30,
                'permission' => 0644,
            ],
        ]);
    }

    /**
     * Configure rate limiting
     */
    protected function configureRateLimiting(): void
    {
        // Configure rate limiting rules
        Config::set([
            'rate_limiting.rules' => [
                'api' => [
                    'limit' => 60,
                    'window' => 60, // seconds
                ],
                'auth' => [
                    'limit' => 5,
                    'window' => 60,
                ],
                'upload' => [
                    'limit' => 10,
                    'window' => 60,
                ],
                'download' => [
                    'limit' => 30,
                    'window' => 60,
                ],
                'search' => [
                    'limit' => 100,
                    'window' => 60,
                ],
            ],
            'rate_limiting.storage' => 'redis', // or 'cache'
            'rate_limiting.key_generator' => function (Request $request) {
                return $request->user()?->id ?? $request->ip();
            },
        ]);
    }

    /**
     * Register security event listeners
     */
    protected function registerSecurityEventListeners(): void
    {
        // Listen for authentication events
        $this->app['events']->listen('auth.login', function ($event) {
            $this->logSecurityEvent('user_login', [
                'user_id' => $event->user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        $this->app['events']->listen('auth.logout', function ($event) {
            $this->logSecurityEvent('user_logout', [
                'user_id' => $event->user->id,
                'ip_address' => request()->ip(),
            ]);
        });

        $this->app['events']->listen('auth.failed', function ($event) {
            $this->logSecurityEvent('login_failed', [
                'email' => $event->credentials['email'] ?? 'unknown',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        // Listen for password change events
        $this->app['events']->listen('password.changed', function ($event) {
            $this->logSecurityEvent('password_changed', [
                'user_id' => $event->user->id,
                'ip_address' => request()->ip(),
            ]);
        });

        // Listen for suspicious activity
        $this->app['events']->listen('security.suspicious', function ($event) {
            $this->logSecurityEvent('suspicious_activity', [
                'type' => $event->type,
                'details' => $event->details,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Check module access for user
     */
    protected function checkModuleAccess($user, string $module): bool
    {
        if (!$user) {
            return false;
        }

        // Admin has access to all modules
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check module-specific permissions
        $modulePermissions = [
            'student' => ['admin', 'principal', 'teacher', 'student'],
            'teacher' => ['admin', 'principal', 'teacher'],
            'attendance' => ['admin', 'principal', 'teacher'],
            'academic' => ['admin', 'principal', 'teacher'],
            'exam' => ['admin', 'principal', 'teacher'],
            'fee' => ['admin', 'principal'],
            'library' => ['admin', 'principal', 'librarian'],
            'transport' => ['admin', 'principal', 'transport_manager'],
            'communication' => ['admin', 'principal', 'teacher'],
            'reports' => ['admin', 'principal'],
            'settings' => ['admin'],
        ];

        $allowedRoles = $modulePermissions[strtolower($module)] ?? [];
        
        return $user->hasAnyRole($allowedRoles);
    }

    /**
     * Check file access permissions
     */
    protected function checkFileAccess($user, string $fileType, $fileId): bool
    {
        if (!$user) {
            return false;
        }

        // Admin has access to all files
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check file type specific access
        switch ($fileType) {
            case 'student_document':
                return $user->hasAnyRole(['admin', 'principal', 'teacher']) || 
                       ($user->hasRole('student') && $this->isOwnDocument($user, $fileId));
                       
            case 'teacher_document':
                return $user->hasAnyRole(['admin', 'principal']) || 
                       ($user->hasRole('teacher') && $this->isOwnDocument($user, $fileId));
                       
            case 'academic_document':
                return $user->hasAnyRole(['admin', 'principal', 'teacher']);
                
            case 'report':
                return $user->hasAnyRole(['admin', 'principal']);
                
            default:
                return false;
        }
    }

    /**
     * Check sensitive operation permissions
     */
    protected function checkSensitiveOperation($user, string $operation): bool
    {
        if (!$user) {
            return false;
        }

        $sensitiveOperations = [
            'delete_user' => ['admin'],
            'modify_grades' => ['admin', 'principal'],
            'access_financial_data' => ['admin', 'principal'],
            'system_backup' => ['admin'],
            'user_impersonation' => ['admin'],
            'bulk_operations' => ['admin', 'principal'],
        ];

        $allowedRoles = $sensitiveOperations[$operation] ?? [];
        
        return $user->hasAnyRole($allowedRoles);
    }

    /**
     * Check if document belongs to user
     */
    protected function isOwnDocument($user, $fileId): bool
    {
        // This would typically query the database to check ownership
        // For now, return false as a safe default
        return false;
    }

    /**
     * Log security event
     */
    protected function logSecurityEvent(string $event, array $data): void
    {
        Log::channel('security')->info("Security Event: {$event}", $data);
        
        // Also log to audit trail if available
        if ($this->app->bound('audit.logger')) {
            $this->app['audit.logger']->logSecurityEvent($event, $data);
        }
    }
}