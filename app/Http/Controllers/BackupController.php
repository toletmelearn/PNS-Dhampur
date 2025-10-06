<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class BackupController extends Controller
{
    /**
     * Display backup management dashboard
     */
    public function index()
    {
        $backupLogs = DB::table('backup_logs')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
            
        $backupStats = [
            'total_backups' => DB::table('backup_logs')->count(),
            'successful_backups' => DB::table('backup_logs')->where('status', 'success')->count(),
            'failed_backups' => DB::table('backup_logs')->where('status', 'failed')->count(),
            'last_database_backup' => DB::table('backup_logs')
                ->where('type', 'database')
                ->where('status', 'success')
                ->orderBy('created_at', 'desc')
                ->first(),
            'last_file_backup' => DB::table('backup_logs')
                ->where('type', 'files')
                ->where('status', 'success')
                ->orderBy('created_at', 'desc')
                ->first(),
        ];
        
        return view('admin.backups.index', compact('backupLogs', 'backupStats'));
    }
    
    /**
     * Create database backup
     */
    public function createDatabaseBackup(Request $request)
    {
        try {
            $incremental = $request->boolean('incremental', false);
            $compress = $request->boolean('compress', true);
            
            $options = [];
            if ($incremental) $options[] = '--incremental';
            if ($compress) $options[] = '--compress';
            
            Artisan::call('backup:database', $options);
            
            return response()->json([
                'success' => true,
                'message' => 'Database backup created successfully',
                'output' => Artisan::output()
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database backup failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create file backup
     */
    public function createFileBackup(Request $request)
    {
        try {
            $directories = $request->input('directories', ['storage', 'public']);
            $exclude = $request->input('exclude', ['logs', 'cache']);
            
            $options = [
                '--directories' => implode(',', $directories),
                '--exclude' => implode(',', $exclude)
            ];
            
            Artisan::call('backup:files', $options);
            
            return response()->json([
                'success' => true,
                'message' => 'File backup created successfully',
                'output' => Artisan::output()
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File backup failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export data
     */
    public function exportData(Request $request)
    {
        try {
            $format = $request->input('format', 'json');
            $tables = $request->input('tables', []);
            $excludeTables = $request->input('exclude_tables', []);
            
            $options = ['--format' => $format];
            
            if (empty($tables)) {
                $options['--all'] = true;
            } else {
                $options['--tables'] = implode(',', $tables);
            }
            
            if (!empty($excludeTables)) {
                $options['--exclude'] = implode(',', $excludeTables);
            }
            
            Artisan::call('data:export', $options);
            
            return response()->json([
                'success' => true,
                'message' => 'Data export completed successfully',
                'output' => Artisan::output()
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data export failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Import data
     */
    public function importData(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|max:102400', // 100MB max
            'format' => 'in:auto,json,csv,sql',
            'truncate' => 'boolean',
            'ignore_errors' => 'boolean'
        ]);
        
        try {
            $file = $request->file('backup_file');
            $filePath = $file->store('imports', 'local');
            $fullPath = storage_path('app/' . $filePath);
            
            $options = [
                'file' => $fullPath,
                '--format' => $request->input('format', 'auto')
            ];
            
            if ($request->boolean('truncate')) {
                $options['--truncate'] = true;
            }
            
            if ($request->boolean('ignore_errors')) {
                $options['--ignore-errors'] = true;
            }
            
            Artisan::call('data:import', $options);
            
            // Clean up uploaded file
            Storage::disk('local')->delete($filePath);
            
            return response()->json([
                'success' => true,
                'message' => 'Data import completed successfully',
                'output' => Artisan::output()
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data import failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get backup logs
     */
    public function getLogs(Request $request)
    {
        $query = DB::table('backup_logs');
        
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }
        
        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }
        
        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));
            
        return response()->json($logs);
    }
    
    /**
     * Download backup file
     */
    public function downloadBackup($id)
    {
        try {
            $backup = DB::table('backup_logs')->where('id', $id)->first();
            
            if (!$backup) {
                return response()->json(['error' => 'Backup not found'], 404);
            }
            
            if (!$backup->file_path || !file_exists($backup->file_path)) {
                return response()->json(['error' => 'Backup file not found'], 404);
            }
            
            return response()->download($backup->file_path, $backup->filename);
            
        } catch (Exception $e) {
            return response()->json(['error' => 'Download failed: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Delete backup file
     */
    public function deleteBackup($id)
    {
        try {
            $backup = DB::table('backup_logs')->where('id', $id)->first();
            
            if (!$backup) {
                return response()->json(['error' => 'Backup not found'], 404);
            }
            
            // Delete physical file if exists
            if ($backup->file_path && file_exists($backup->file_path)) {
                unlink($backup->file_path);
            }
            
            // Delete database record
            DB::table('backup_logs')->where('id', $id)->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get backup statistics
     */
    public function getStats()
    {
        $stats = [
            'total_backups' => DB::table('backup_logs')->count(),
            'successful_backups' => DB::table('backup_logs')->where('status', 'success')->count(),
            'failed_backups' => DB::table('backup_logs')->where('status', 'failed')->count(),
            'total_size' => DB::table('backup_logs')->where('status', 'success')->sum('file_size'),
            'backup_types' => DB::table('backup_logs')
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->get(),
            'recent_activity' => DB::table('backup_logs')
                ->select('type', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'storage_usage' => $this->getStorageUsage()
        ];
        
        return response()->json($stats);
    }
    
    /**
     * Get storage usage information
     */
    private function getStorageUsage()
    {
        $backupPath = storage_path('app/backups');
        
        if (!is_dir($backupPath)) {
            return ['used' => 0, 'available' => 0];
        }
        
        $used = 0;
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupPath)
        );
        
        foreach ($files as $file) {
            if ($file->isFile()) {
                $used += $file->getSize();
            }
        }
        
        $available = disk_free_space($backupPath);
        
        return [
            'used' => $used,
            'available' => $available,
            'used_formatted' => $this->formatBytes($used),
            'available_formatted' => $this->formatBytes($available)
        ];
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
