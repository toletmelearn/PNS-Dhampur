<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class SystemMaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display the maintenance dashboard
     */
    public function index()
    {
        $systemInfo = $this->getSystemInfo();
        $cacheInfo = $this->getCacheInfo();
        $databaseInfo = $this->getDatabaseInfo();
        $logInfo = $this->getLogInfo();

        return view('admin.maintenance.index', compact('systemInfo', 'cacheInfo', 'databaseInfo', 'logInfo'));
    }

    /**
     * Clear application cache
     */
    public function clearCache(Request $request)
    {
        try {
            $cacheTypes = $request->input('cache_types', ['config', 'route', 'view', 'application']);
            $results = [];

            foreach ($cacheTypes as $type) {
                switch ($type) {
                    case 'config':
                        Artisan::call('config:clear');
                        $results['config'] = 'Configuration cache cleared';
                        break;
                    case 'route':
                        Artisan::call('route:clear');
                        $results['route'] = 'Route cache cleared';
                        break;
                    case 'view':
                        Artisan::call('view:clear');
                        $results['view'] = 'View cache cleared';
                        break;
                    case 'application':
                        Cache::flush();
                        $results['application'] = 'Application cache cleared';
                        break;
                    case 'compiled':
                        Artisan::call('clear-compiled');
                        $results['compiled'] = 'Compiled classes cleared';
                        break;
                }
            }

            Log::info('Cache cleared by user: ' . auth()->user()->name, ['types' => $cacheTypes]);

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Cache clear failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Optimize database
     */
    public function optimizeDatabase(Request $request)
    {
        try {
            $operations = $request->input('operations', ['optimize', 'analyze']);
            $results = [];

            foreach ($operations as $operation) {
                switch ($operation) {
                    case 'optimize':
                        // Get all tables
                        $tables = DB::select('SHOW TABLES');
                        $tableColumn = 'Tables_in_' . env('DB_DATABASE');
                        
                        foreach ($tables as $table) {
                            $tableName = $table->$tableColumn;
                            DB::statement("OPTIMIZE TABLE `{$tableName}`");
                        }
                        $results['optimize'] = 'Database tables optimized';
                        break;
                        
                    case 'analyze':
                        $tables = DB::select('SHOW TABLES');
                        $tableColumn = 'Tables_in_' . env('DB_DATABASE');
                        
                        foreach ($tables as $table) {
                            $tableName = $table->$tableColumn;
                            DB::statement("ANALYZE TABLE `{$tableName}`");
                        }
                        $results['analyze'] = 'Database tables analyzed';
                        break;
                        
                    case 'repair':
                        $tables = DB::select('SHOW TABLES');
                        $tableColumn = 'Tables_in_' . env('DB_DATABASE');
                        
                        foreach ($tables as $table) {
                            $tableName = $table->$tableColumn;
                            DB::statement("REPAIR TABLE `{$tableName}`");
                        }
                        $results['repair'] = 'Database tables repaired';
                        break;
                }
            }

            Log::info('Database optimization performed by user: ' . auth()->user()->name, ['operations' => $operations]);

            return response()->json([
                'success' => true,
                'message' => 'Database optimization completed',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Database optimization failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to optimize database: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View log files
     */
    public function viewLogs(Request $request)
    {
        try {
            $logFile = $request->input('file', 'laravel.log');
            $lines = $request->input('lines', 100);
            $level = $request->input('level', 'all');
            $search = $request->input('search', '');

            $logPath = storage_path('logs/' . $logFile);

            if (!File::exists($logPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log file not found'
                ], 404);
            }

            $logContent = File::get($logPath);
            $logLines = array_reverse(explode("\n", $logContent));
            
            // Filter by level if specified
            if ($level !== 'all') {
                $logLines = array_filter($logLines, function($line) use ($level) {
                    return strpos(strtolower($line), strtolower($level)) !== false;
                });
            }

            // Filter by search term if specified
            if (!empty($search)) {
                $logLines = array_filter($logLines, function($line) use ($search) {
                    return stripos($line, $search) !== false;
                });
            }

            // Limit lines
            $logLines = array_slice($logLines, 0, $lines);

            return response()->json([
                'success' => true,
                'data' => [
                    'file' => $logFile,
                    'lines' => array_values($logLines),
                    'total_lines' => count($logLines),
                    'file_size' => File::size($logPath),
                    'last_modified' => File::lastModified($logPath)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Log viewing failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to view logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available log files
     */
    public function getLogFiles()
    {
        try {
            $logPath = storage_path('logs');
            $files = File::files($logPath);
            
            $logFiles = [];
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $logFiles[] = [
                        'name' => basename($file),
                        'size' => File::size($file),
                        'modified' => File::lastModified($file),
                        'readable' => File::isReadable($file)
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $logFiles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get log files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear log files
     */
    public function clearLogs(Request $request)
    {
        try {
            $files = $request->input('files', []);
            $results = [];

            if (empty($files)) {
                // Clear all log files
                $logPath = storage_path('logs');
                $allFiles = File::files($logPath);
                
                foreach ($allFiles as $file) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                        File::put($file, '');
                        $results[] = basename($file) . ' cleared';
                    }
                }
            } else {
                // Clear specific files
                foreach ($files as $file) {
                    $logPath = storage_path('logs/' . $file);
                    if (File::exists($logPath)) {
                        File::put($logPath, '');
                        $results[] = $file . ' cleared';
                    }
                }
            }

            Log::info('Log files cleared by user: ' . auth()->user()->name, ['files' => $files]);

            return response()->json([
                'success' => true,
                'message' => 'Log files cleared successfully',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Log clearing failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * System update check
     */
    public function checkUpdates()
    {
        try {
            // Check Laravel version
            $currentVersion = app()->version();
            
            // Check composer packages (simplified)
            $composerLock = json_decode(File::get(base_path('composer.lock')), true);
            $packages = $composerLock['packages'] ?? [];
            
            $updateInfo = [
                'laravel' => [
                    'current' => $currentVersion,
                    'latest' => $this->getLatestLaravelVersion(),
                    'update_available' => false
                ],
                'packages' => [],
                'security_updates' => 0,
                'last_check' => now()->toDateTimeString()
            ];

            return response()->json([
                'success' => true,
                'data' => $updateInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check updates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run system maintenance
     */
    public function runMaintenance(Request $request)
    {
        try {
            $tasks = $request->input('tasks', ['cache', 'database', 'logs']);
            $results = [];

            foreach ($tasks as $task) {
                switch ($task) {
                    case 'cache':
                        Cache::flush();
                        Artisan::call('config:clear');
                        Artisan::call('route:clear');
                        Artisan::call('view:clear');
                        $results['cache'] = 'All caches cleared';
                        break;
                        
                    case 'database':
                        $tables = DB::select('SHOW TABLES');
                        $tableColumn = 'Tables_in_' . env('DB_DATABASE');
                        
                        foreach ($tables as $table) {
                            $tableName = $table->$tableColumn;
                            DB::statement("OPTIMIZE TABLE `{$tableName}`");
                        }
                        $results['database'] = 'Database optimized';
                        break;
                        
                    case 'logs':
                        $logPath = storage_path('logs');
                        $files = File::files($logPath);
                        
                        foreach ($files as $file) {
                            if (pathinfo($file, PATHINFO_EXTENSION) === 'log' && File::size($file) > 10 * 1024 * 1024) {
                                File::put($file, '');
                            }
                        }
                        $results['logs'] = 'Large log files cleared';
                        break;
                }
            }

            Log::info('System maintenance performed by user: ' . auth()->user()->name, ['tasks' => $tasks]);

            return response()->json([
                'success' => true,
                'message' => 'System maintenance completed',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('System maintenance failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to run maintenance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system information
     */
    private function getSystemInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'disk_space' => [
                'total' => disk_total_space(base_path()),
                'free' => disk_free_space(base_path())
            ]
        ];
    }

    /**
     * Get cache information
     */
    private function getCacheInfo()
    {
        return [
            'driver' => config('cache.default'),
            'config_cached' => File::exists(base_path('bootstrap/cache/config.php')),
            'routes_cached' => File::exists(base_path('bootstrap/cache/routes-v7.php')),
            'views_cached' => count(File::glob(storage_path('framework/views/*.php'))) > 0
        ];
    }

    /**
     * Get database information
     */
    private function getDatabaseInfo()
    {
        try {
            $connection = DB::connection();
            $databaseName = $connection->getDatabaseName();
            
            // Get database size
            $size = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size' FROM information_schema.tables WHERE table_schema = ?", [$databaseName]);
            
            // Get table count
            $tableCount = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?", [$databaseName]);

            return [
                'connection' => 'Connected',
                'database' => $databaseName,
                'size_mb' => $size[0]->size ?? 0,
                'table_count' => $tableCount[0]->count ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'connection' => 'Failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get log information
     */
    private function getLogInfo()
    {
        try {
            $logPath = storage_path('logs');
            $files = File::files($logPath);
            
            $totalSize = 0;
            $fileCount = 0;
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $totalSize += File::size($file);
                    $fileCount++;
                }
            }

            return [
                'file_count' => $fileCount,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'path' => $logPath
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get latest Laravel version (simplified)
     */
    private function getLatestLaravelVersion()
    {
        // This would typically make an API call to check for updates
        // For now, return current version
        return app()->version();
    }
}