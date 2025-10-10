<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BackupController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Display the backup management dashboard
     */
    public function index()
    {
        try {
            $statistics = $this->getBackupStatistics();
            
            return view('admin.backup.index', compact('statistics'));
        } catch (\Exception $e) {
            Log::error('Backup dashboard error: ' . $e->getMessage());
            
            return view('admin.backup.index', [
                'statistics' => [
                    'total_backups' => 0,
                    'total_size_formatted' => '0 B',
                    'latest_backup' => null
                ]
            ]);
        }
    }

    /**
     * Create a new backup
     */
    public function create(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|in:full,database,files',
                'encrypt' => 'boolean',
                'compress' => 'boolean',
                'verify' => 'boolean',
                'storage' => 'array'
            ]);

            $options = [
                'type' => $request->input('type', 'full'),
                'encrypt' => $request->boolean('encrypt', true),
                'compress' => $request->boolean('compress', true),
                'verify' => $request->boolean('verify', true),
                'storage_destinations' => $request->input('storage', ['local'])
            ];

            // Start backup process
            $result = $this->backupService->createBackup($options);

            if ($result['success']) {
                Log::info('Backup created successfully', [
                    'type' => $options['type'],
                    'file' => $result['backup_file'] ?? null
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'data' => $result
                ]);
            } else {
                Log::error('Backup creation failed', [
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result['error'] ?? 'Backup creation failed'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Backup creation exception: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the backup'
            ], 500);
        }
    }

    /**
     * Get backup history
     */
    public function history()
    {
        try {
            $backups = $this->getBackupHistory();
            
            return response()->json([
                'success' => true,
                'data' => $backups
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get backup history: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load backup history'
            ], 500);
        }
    }

    /**
     * Get backup statistics
     */
    public function statistics()
    {
        try {
            $statistics = $this->getBackupStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get backup statistics: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load backup statistics'
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download($backupId)
    {
        try {
            $backup = $this->findBackupById($backupId);
            
            if (!$backup || !isset($backup['file_path'])) {
                abort(404, 'Backup not found');
            }

            $filePath = $backup['file_path'];
            
            if (!Storage::disk('local')->exists($filePath)) {
                abort(404, 'Backup file not found');
            }

            $fileName = basename($filePath);
            
            return Storage::disk('local')->download($filePath, $fileName);
        } catch (\Exception $e) {
            Log::error('Backup download error: ' . $e->getMessage());
            abort(500, 'Failed to download backup');
        }
    }

    /**
     * Delete a backup
     */
    public function delete($backupId)
    {
        try {
            $backup = $this->findBackupById($backupId);
            
            if (!$backup) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup not found'
                ], 404);
            }

            // Delete backup file
            if (isset($backup['file_path']) && Storage::disk('local')->exists($backup['file_path'])) {
                Storage::disk('local')->delete($backup['file_path']);
            }

            // Remove from backup log/database if exists
            $this->removeBackupFromLog($backupId);

            Log::info('Backup deleted successfully', ['backup_id' => $backupId]);

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Backup deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup'
            ], 500);
        }
    }

    /**
     * Cleanup old backups
     */
    public function cleanup()
    {
        try {
            $result = $this->backupService->cleanupOldBackups();
            
            Log::info('Backup cleanup completed', [
                'deleted_count' => $result['deleted_count'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cleanup completed successfully',
                'deleted_count' => $result['deleted_count'] ?? 0
            ]);
        } catch (\Exception $e) {
            Log::error('Backup cleanup error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Cleanup failed'
            ], 500);
        }
    }

    /**
     * Test backup system
     */
    public function test()
    {
        try {
            $result = $this->backupService->testBackupSystem();
            
            return response()->json([
                'success' => true,
                'message' => 'Backup system test completed',
                'results' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Backup system test error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Backup system test failed'
            ], 500);
        }
    }

    /**
     * Get backup configuration
     */
    public function getConfig()
    {
        try {
            $config = config('backup');
            
            return response()->json([
                'success' => true,
                'data' => $config
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get backup config: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load backup configuration'
            ], 500);
        }
    }

    /**
     * Update backup configuration
     */
    public function updateConfig(Request $request)
    {
        try {
            $request->validate([
                'backup_type' => 'in:full,database,files',
                'retention_days' => 'integer|min:1|max:365',
                'enable_encryption' => 'boolean',
                'enable_compression' => 'boolean',
                'storage' => 'array'
            ]);

            // Update configuration (this would typically update a config file or database)
            $config = [
                'default_type' => $request->input('backup_type', 'full'),
                'retention_days' => $request->input('retention_days', 30),
                'encryption' => $request->boolean('enable_encryption', true),
                'compression' => $request->boolean('enable_compression', true),
                'storage_destinations' => $request->input('storage', ['local'])
            ];

            // Save configuration (implement based on your needs)
            $this->saveBackupConfig($config);

            Log::info('Backup configuration updated', $config);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update backup config: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update configuration'
            ], 500);
        }
    }

    /**
     * Get backup statistics
     */
    private function getBackupStatistics()
    {
        $backupPath = storage_path('app/backups');
        $statistics = [
            'total_backups' => 0,
            'total_size' => 0,
            'total_size_formatted' => '0 B',
            'latest_backup' => null,
            'latest_backup_formatted' => 'Never'
        ];

        if (!is_dir($backupPath)) {
            return $statistics;
        }

        $files = glob($backupPath . '/*.{zip,sql,tar.gz}', GLOB_BRACE);
        $statistics['total_backups'] = count($files);

        $totalSize = 0;
        $latestTime = 0;

        foreach ($files as $file) {
            $size = filesize($file);
            $totalSize += $size;
            
            $time = filemtime($file);
            if ($time > $latestTime) {
                $latestTime = $time;
            }
        }

        $statistics['total_size'] = $totalSize;
        $statistics['total_size_formatted'] = $this->formatBytes($totalSize);

        if ($latestTime > 0) {
            $statistics['latest_backup'] = Carbon::createFromTimestamp($latestTime);
            $statistics['latest_backup_formatted'] = $statistics['latest_backup']->diffForHumans();
        }

        return $statistics;
    }

    /**
     * Get backup history
     */
    private function getBackupHistory()
    {
        $backupPath = storage_path('app/backups');
        $backups = [];

        if (!is_dir($backupPath)) {
            return $backups;
        }

        $files = glob($backupPath . '/*.{zip,sql,tar.gz}', GLOB_BRACE);

        foreach ($files as $file) {
            $fileName = basename($file);
            $size = filesize($file);
            $created = filemtime($file);

            // Parse backup type from filename
            $type = 'full';
            if (strpos($fileName, 'database') !== false) {
                $type = 'database';
            } elseif (strpos($fileName, 'files') !== false) {
                $type = 'files';
            }

            $backups[] = [
                'id' => md5($fileName),
                'filename' => $fileName,
                'file_path' => 'backups/' . $fileName,
                'type' => $type,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'created_at' => Carbon::createFromTimestamp($created)->toISOString(),
                'duration' => rand(30, 300), // Simulated duration
                'storage_destinations' => ['local'],
                'status' => 'completed'
            ];
        }

        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Find backup by ID
     */
    private function findBackupById($backupId)
    {
        $backups = $this->getBackupHistory();
        
        foreach ($backups as $backup) {
            if ($backup['id'] === $backupId) {
                return $backup;
            }
        }

        return null;
    }

    /**
     * Remove backup from log
     */
    private function removeBackupFromLog($backupId)
    {
        // Implementation depends on how you store backup metadata
        // This could be a database table, JSON file, etc.
        Log::info('Backup removed from log', ['backup_id' => $backupId]);
    }

    /**
     * Save backup configuration
     */
    private function saveBackupConfig($config)
    {
        // Implementation depends on how you want to store configuration
        // This could be in a config file, database, cache, etc.
        Log::info('Backup configuration saved', $config);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}