<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class HealthCheckController extends Controller
{
    /**
     * Perform a comprehensive health check of the application
     */
    public function check(Request $request)
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'environment' => $this->checkEnvironment(),
            'security' => $this->checkSecurity(),
        ];

        $overallStatus = collect($checks)->every(fn($check) => $check['status'] === 'ok') ? 'healthy' : 'unhealthy';

        $response = [
            'status' => $overallStatus,
            'timestamp' => Carbon::now()->toISOString(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ];

        $httpStatus = $overallStatus === 'healthy' ? 200 : 503;

        return response()->json($response, $httpStatus);
    }

    /**
     * Check database connectivity
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $userCount = DB::table('users')->count();
            
            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
                'details' => [
                    'connection' => DB::connection()->getName(),
                    'user_count' => $userCount,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check cache functionality
     */
    private function checkCache(): array
    {
        try {
            $key = 'health_check_' . time();
            $value = 'test_value';
            
            Cache::put($key, $value, 60);
            $retrieved = Cache::get($key);
            Cache::forget($key);
            
            if ($retrieved === $value) {
                return [
                    'status' => 'ok',
                    'message' => 'Cache is working properly',
                    'driver' => config('cache.default')
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Cache read/write test failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache system error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check storage accessibility
     */
    private function checkStorage(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test file';
            
            Storage::put($testFile, $testContent);
            $retrieved = Storage::get($testFile);
            Storage::delete($testFile);
            
            if ($retrieved === $testContent) {
                return [
                    'status' => 'ok',
                    'message' => 'Storage is accessible',
                    'driver' => config('filesystems.default')
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Storage read/write test failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage system error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check environment configuration
     */
    private function checkEnvironment(): array
    {
        $issues = [];
        
        // Check critical environment variables
        $requiredEnvVars = ['APP_KEY', 'DB_DATABASE', 'DB_USERNAME'];
        foreach ($requiredEnvVars as $var) {
            if (empty(config(strtolower(str_replace('_', '.', $var))))) {
                $issues[] = "Missing or empty environment variable: {$var}";
            }
        }
        
        // Check debug mode in production
        if (config('app.env') === 'production' && config('app.debug') === true) {
            $issues[] = 'Debug mode is enabled in production environment';
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            $issues[] = 'PHP version is below recommended 8.0.0';
        }
        
        return [
            'status' => empty($issues) ? 'ok' : 'warning',
            'message' => empty($issues) ? 'Environment configuration is valid' : 'Environment configuration issues detected',
            'issues' => $issues,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version()
        ];
    }

    /**
     * Check security configuration
     */
    private function checkSecurity(): array
    {
        $issues = [];
        
        // Check HTTPS in production
        if (config('app.env') === 'production' && !request()->isSecure()) {
            $issues[] = 'HTTPS is not enabled in production';
        }
        
        // Check session security
        if (config('session.secure') === false && config('app.env') === 'production') {
            $issues[] = 'Session cookies are not marked as secure in production';
        }
        
        // Check CSRF protection
        if (!config('app.csrf_protection', true)) {
            $issues[] = 'CSRF protection is disabled';
        }
        
        return [
            'status' => empty($issues) ? 'ok' : 'warning',
            'message' => empty($issues) ? 'Security configuration is adequate' : 'Security configuration issues detected',
            'issues' => $issues
        ];
    }

    /**
     * Simple ping endpoint for load balancers
     */
    public function ping()
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'pong',
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }
}