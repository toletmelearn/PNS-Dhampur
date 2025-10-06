<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use ZipArchive;
use Exception;

class FileBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:files {--type=full : Type of backup (full, incremental)} {--exclude= : Comma-separated list of directories to exclude}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a backup of application files and user uploads';

    /**
     * Directories to backup
     */
    private $backupDirectories = [
        'storage/app/public',
        'public/uploads',
        'public/assets',
        'resources/views',
        'app',
        'config',
        'database/migrations',
        'database/seeders'
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting file backup...');
        
        try {
            $backupType = $this->option('type');
            $excludeOption = $this->option('exclude');
            $excludeDirs = $excludeOption ? explode(',', $excludeOption) : [];
            
            // Create backup directory if it doesn't exist
            $backupDir = storage_path('app/backups/files');
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Generate backup filename
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "files_backup_{$backupType}_{$timestamp}.zip";
            $filepath = $backupDir . '/' . $filename;
            
            // Create ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                throw new Exception('Cannot create ZIP file: ' . $filepath);
            }
            
            $totalFiles = 0;
            $totalSize = 0;
            
            // Add directories to backup
            foreach ($this->backupDirectories as $directory) {
                if (in_array($directory, $excludeDirs)) {
                    $this->info("⏭️  Skipping excluded directory: {$directory}");
                    continue;
                }
                
                $fullPath = base_path($directory);
                if (is_dir($fullPath)) {
                    $this->info("📁 Backing up directory: {$directory}");
                    $result = $this->addDirectoryToZip($zip, $fullPath, $directory);
                    $totalFiles += $result['files'];
                    $totalSize += $result['size'];
                } else {
                    $this->warn("⚠️  Directory not found: {$directory}");
                }
            }
            
            // Add .env file (important for configuration)
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $zip->addFile($envPath, '.env');
                $totalFiles++;
                $totalSize += filesize($envPath);
                $this->info("📄 Added .env file");
            }
            
            // Add composer files
            $composerFiles = ['composer.json', 'composer.lock', 'package.json', 'package-lock.json'];
            foreach ($composerFiles as $file) {
                $filePath = base_path($file);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $file);
                    $totalFiles++;
                    $totalSize += filesize($filePath);
                    $this->info("📄 Added {$file}");
                }
            }
            
            $zip->close();
            
            // Get final file size
            $backupSize = $this->formatBytes(filesize($filepath));
            $originalSize = $this->formatBytes($totalSize);
            
            // Log backup information
            $this->logBackup($filename, $filepath, $backupSize, $backupType, $totalFiles);
            
            // Clean old backups (keep last 5 file backups)
            $this->cleanOldBackups($backupDir);
            
            $this->info("✅ File backup completed successfully!");
            $this->info("📁 File: {$filename}");
            $this->info("📊 Backup Size: {$backupSize}");
            $this->info("📈 Original Size: {$originalSize}");
            $this->info("📄 Files: {$totalFiles}");
            $this->info("📍 Location: {$filepath}");
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error("❌ File backup failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Add directory to ZIP archive recursively
     */
    private function addDirectoryToZip($zip, $sourcePath, $zipPath)
    {
        $files = 0;
        $size = 0;
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $zipPath . '/' . substr($filePath, strlen($sourcePath) + 1);
            
            // Replace backslashes with forward slashes for ZIP compatibility
            $relativePath = str_replace('\\', '/', $relativePath);
            
            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile()) {
                // Skip certain file types
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['tmp', 'log', 'cache'])) {
                    continue;
                }
                
                $zip->addFile($filePath, $relativePath);
                $files++;
                $size += $file->getSize();
            }
        }
        
        return ['files' => $files, 'size' => $size];
    }
    
    /**
     * Log backup information to database
     */
    private function logBackup($filename, $filepath, $fileSize, $type, $fileCount)
    {
        try {
            DB::table('backup_logs')->insert([
                'type' => 'files',
                'backup_type' => $type,
                'filename' => $filename,
                'filepath' => $filepath,
                'file_size' => $fileSize,
                'file_count' => $fileCount,
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
        $files = glob($backupDir . '/files_backup_*.zip');
        
        if (count($files) > 5) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove oldest files, keep last 5
            $filesToDelete = array_slice($files, 0, count($files) - 5);
            
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
