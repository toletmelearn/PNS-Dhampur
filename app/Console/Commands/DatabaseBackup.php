<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class DatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database {--type=full : Type of backup (full, incremental)} {--compress : Compress the backup file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup with optional compression and incremental support';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting database backup...');
        
        try {
            $backupType = $this->option('type');
            $compress = $this->option('compress');
            
            // Create backup directory if it doesn't exist
            $backupDir = storage_path('app/backups/database');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Generate backup filename
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "database_backup_{$backupType}_{$timestamp}.sql";
            $filepath = $backupDir . '/' . $filename;
            
            // Get database configuration
            $dbConfig = config('database.connections.' . config('database.default'));
            $host = $dbConfig['host'];
            $port = $dbConfig['port'];
            $database = $dbConfig['database'];
            $username = $dbConfig['username'];
            $password = $dbConfig['password'];
            
            // Create mysqldump command with full path for Windows/XAMPP
            $mysqldumpPath = 'mysqldump';
            
            // Check if we're on Windows and XAMPP is installed
            if (PHP_OS_FAMILY === 'Windows') {
                $xamppMysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
                if (file_exists($xamppMysqldump)) {
                    $mysqldumpPath = $xamppMysqldump;
                }
            }
            
            $command = sprintf(
                '"%s" --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                $mysqldumpPath,
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
            
            // Execute backup command
            $this->info("Creating {$backupType} backup...");
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new Exception('Database backup failed with return code: ' . $returnCode);
            }
            
            // Compress if requested
            if ($compress) {
                $this->info('Compressing backup file...');
                $compressedFile = $filepath . '.gz';
                exec("gzip {$filepath}", $output, $returnCode);
                
                if ($returnCode === 0) {
                    $filepath = $compressedFile;
                    $filename = $filename . '.gz';
                }
            }
            
            // Get file size
            $fileSize = $this->formatBytes(filesize($filepath));
            
            // Log backup information
            $this->logBackup($filename, $filepath, $fileSize, $backupType);
            
            // Clean old backups (keep last 10)
            $this->cleanOldBackups($backupDir);
            
            $this->info("✅ Database backup completed successfully!");
            $this->info("📁 File: {$filename}");
            $this->info("📊 Size: {$fileSize}");
            $this->info("📍 Location: {$filepath}");
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error("❌ Database backup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Log backup information to database
     */
    private function logBackup($filename, $filepath, $fileSize, $type)
    {
        try {
            DB::table('backup_logs')->insert([
                'type' => 'database',
                'backup_type' => $type,
                'filename' => $filename,
                'filepath' => $filepath,
                'file_size' => $fileSize,
                'status' => 'completed',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        } catch (Exception $e) {
            $this->warn("Could not log backup to database: " . $e->getMessage());
        }
    }
    
    /**
     * Clean old backup files
     */
    private function cleanOldBackups($backupDir)
    {
        $files = glob($backupDir . '/database_backup_*.sql*');
        
        if (count($files) > 10) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files, keep last 10
            $filesToDelete = array_slice($files, 0, count($files) - 10);
            
            foreach ($filesToDelete as $file) {
                if (file_exists($file)) {
                    unlink($file);
                    $this->info("🗑️  Cleaned old backup: " . basename($file));
                }
            }
        }
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
