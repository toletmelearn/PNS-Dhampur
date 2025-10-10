<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use ZipArchive;

class BackupSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:system 
                            {--type=full : Type of backup (full, database, files)}
                            {--encrypt : Encrypt the backup}
                            {--compress : Compress the backup}
                            {--storage=* : Storage destinations (local, s3, ftp)}
                            {--retention=30 : Days to retain backups}
                            {--verify : Verify backup integrity}';

    /**
     * The console command description.
     */
    protected $description = 'Create automated system backups with encryption and multiple storage destinations';

    /**
     * Backup configuration
     */
    private $config;
    private $backupPath;
    private $timestamp;
    private $backupName;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting System Backup Process...');
        
        $this->initializeBackup();
        
        try {
            $backupFiles = [];
            
            // Determine backup type
            $type = $this->option('type');
            
            if ($type === 'full' || $type === 'database') {
                $this->info('📊 Creating database backup...');
                $dbBackup = $this->createDatabaseBackup();
                if ($dbBackup) {
                    $backupFiles[] = $dbBackup;
                    $this->info('✅ Database backup completed');
                }
            }
            
            if ($type === 'full' || $type === 'files') {
                $this->info('📁 Creating file system backup...');
                $fileBackup = $this->createFileSystemBackup();
                if ($fileBackup) {
                    $backupFiles[] = $fileBackup;
                    $this->info('✅ File system backup completed');
                }
            }
            
            if (empty($backupFiles)) {
                $this->error('❌ No backup files were created');
                return 1;
            }
            
            // Compress backups if requested
            if ($this->option('compress')) {
                $this->info('🗜️ Compressing backup files...');
                $compressedBackup = $this->compressBackups($backupFiles);
                if ($compressedBackup) {
                    $backupFiles = [$compressedBackup];
                    $this->info('✅ Backup compression completed');
                }
            }
            
            // Encrypt backups if requested
            if ($this->option('encrypt')) {
                $this->info('🔐 Encrypting backup files...');
                $encryptedFiles = [];
                foreach ($backupFiles as $file) {
                    $encrypted = $this->encryptBackup($file);
                    if ($encrypted) {
                        $encryptedFiles[] = $encrypted;
                        // Remove unencrypted file
                        File::delete($file);
                    }
                }
                $backupFiles = $encryptedFiles;
                $this->info('✅ Backup encryption completed');
            }
            
            // Upload to storage destinations
            $storageDestinations = $this->option('storage') ?: ['local'];
            foreach ($storageDestinations as $storage) {
                $this->info("☁️ Uploading to {$storage} storage...");
                $this->uploadToStorage($backupFiles, $storage);
                $this->info("✅ Upload to {$storage} completed");
            }
            
            // Verify backup integrity if requested
            if ($this->option('verify')) {
                $this->info('🔍 Verifying backup integrity...');
                $this->verifyBackupIntegrity($backupFiles);
                $this->info('✅ Backup verification completed');
            }
            
            // Clean up old backups
            $this->cleanupOldBackups();
            
            // Log backup completion
            $this->logBackupCompletion($backupFiles, $storageDestinations);
            
            $this->info('🎉 System backup completed successfully!');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Backup failed: ' . $e->getMessage());
            Log::error('System backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * Initialize backup configuration
     */
    private function initializeBackup()
    {
        $this->timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $this->backupName = 'backup_' . $this->timestamp;
        $this->backupPath = storage_path('app/backups/' . $this->backupName);
        
        // Create backup directory
        if (!File::exists($this->backupPath)) {
            File::makeDirectory($this->backupPath, 0755, true);
        }
        
        $this->config = config('backup', [
            'encryption_key' => env('BACKUP_ENCRYPTION_KEY', config('app.key')),
            'compression_level' => 9,
            'chunk_size' => 1024 * 1024, // 1MB chunks
        ]);
    }
    
    /**
     * Create database backup
     */
    private function createDatabaseBackup()
    {
        $dbConfig = config('database.connections.' . config('database.default'));
        $filename = $this->backupPath . '/database_' . $this->timestamp . '.sql';
        
        try {
            // Get all table names
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $dbConfig['database'];
            
            $sql = "-- Database Backup\n";
            $sql .= "-- Generated on: " . Carbon::now()->toDateTimeString() . "\n";
            $sql .= "-- Database: " . $dbConfig['database'] . "\n\n";
            
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                
                // Get table structure
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
                $sql .= "-- Table structure for `{$tableName}`\n";
                $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
                $sql .= $createTable->{'Create Table'} . ";\n\n";
                
                // Get table data
                $rows = DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $sql .= "-- Data for table `{$tableName}`\n";
                    $sql .= "INSERT INTO `{$tableName}` VALUES\n";
                    
                    $values = [];
                    foreach ($rows as $row) {
                        $rowData = [];
                        foreach ((array)$row as $value) {
                            if (is_null($value)) {
                                $rowData[] = 'NULL';
                            } else {
                                $rowData[] = "'" . addslashes($value) . "'";
                            }
                        }
                        $values[] = '(' . implode(',', $rowData) . ')';
                    }
                    
                    $sql .= implode(",\n", $values) . ";\n\n";
                }
            }
            
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            File::put($filename, $sql);
            
            return $filename;
            
        } catch (\Exception $e) {
            $this->error('Database backup failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create file system backup
     */
    private function createFileSystemBackup()
    {
        $filename = $this->backupPath . '/files_' . $this->timestamp . '.zip';
        
        try {
            $zip = new ZipArchive();
            if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create zip file');
            }
            
            // Directories to backup
            $backupDirs = [
                'storage/app' => storage_path('app'),
                'public/uploads' => public_path('uploads'),
                'public/images' => public_path('images'),
                'resources/views' => resource_path('views'),
                'config' => config_path(),
            ];
            
            foreach ($backupDirs as $relativePath => $fullPath) {
                if (File::exists($fullPath)) {
                    $this->addDirectoryToZip($zip, $fullPath, $relativePath);
                }
            }
            
            // Add important files
            $importantFiles = [
                '.env.example',
                'composer.json',
                'package.json',
                'artisan',
            ];
            
            foreach ($importantFiles as $file) {
                $filePath = base_path($file);
                if (File::exists($filePath)) {
                    $zip->addFile($filePath, $file);
                }
            }
            
            $zip->close();
            
            return $filename;
            
        } catch (\Exception $e) {
            $this->error('File system backup failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Add directory to zip recursively
     */
    private function addDirectoryToZip($zip, $dir, $relativePath)
    {
        $files = File::allFiles($dir);
        
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativeFilePath = $relativePath . '/' . $file->getRelativePathname();
            $zip->addFile($filePath, $relativeFilePath);
        }
    }
    
    /**
     * Compress backup files
     */
    private function compressBackups($backupFiles)
    {
        $compressedFile = $this->backupPath . '/compressed_' . $this->timestamp . '.zip';
        
        try {
            $zip = new ZipArchive();
            if ($zip->open($compressedFile, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create compressed backup file');
            }
            
            foreach ($backupFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            
            $zip->close();
            
            // Remove original files
            foreach ($backupFiles as $file) {
                File::delete($file);
            }
            
            return $compressedFile;
            
        } catch (\Exception $e) {
            $this->error('Backup compression failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Encrypt backup file
     */
    private function encryptBackup($file)
    {
        $encryptedFile = $file . '.enc';
        
        try {
            $key = $this->config['encryption_key'];
            $iv = random_bytes(16);
            
            $data = File::get($file);
            $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
            
            // Prepend IV to encrypted data
            $encryptedData = base64_encode($iv . $encrypted);
            
            File::put($encryptedFile, $encryptedData);
            
            return $encryptedFile;
            
        } catch (\Exception $e) {
            $this->error('Backup encryption failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Upload backup to storage
     */
    private function uploadToStorage($backupFiles, $storage)
    {
        try {
            switch ($storage) {
                case 'local':
                    // Files are already in local storage
                    break;
                    
                case 's3':
                    foreach ($backupFiles as $file) {
                        $filename = 'backups/' . basename($file);
                        Storage::disk('s3')->put($filename, File::get($file));
                    }
                    break;
                    
                case 'ftp':
                    foreach ($backupFiles as $file) {
                        $filename = 'backups/' . basename($file);
                        Storage::disk('ftp')->put($filename, File::get($file));
                    }
                    break;
                    
                default:
                    $this->warn("Unknown storage destination: {$storage}");
            }
        } catch (\Exception $e) {
            $this->error("Upload to {$storage} failed: " . $e->getMessage());
        }
    }
    
    /**
     * Verify backup integrity
     */
    private function verifyBackupIntegrity($backupFiles)
    {
        foreach ($backupFiles as $file) {
            if (!File::exists($file)) {
                $this->error("Backup file not found: {$file}");
                continue;
            }
            
            $size = File::size($file);
            if ($size === 0) {
                $this->error("Backup file is empty: {$file}");
                continue;
            }
            
            // Generate checksum
            $checksum = hash_file('sha256', $file);
            $checksumFile = $file . '.sha256';
            File::put($checksumFile, $checksum);
            
            $this->info("✅ {$file} - Size: " . $this->formatBytes($size) . " - SHA256: " . substr($checksum, 0, 16) . '...');
        }
    }
    
    /**
     * Clean up old backups
     */
    private function cleanupOldBackups()
    {
        $retention = $this->option('retention');
        $cutoffDate = Carbon::now()->subDays($retention);
        
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            return;
        }
        
        $directories = File::directories($backupDir);
        $deletedCount = 0;
        
        foreach ($directories as $dir) {
            $dirName = basename($dir);
            if (preg_match('/backup_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $dirName, $matches)) {
                $backupDate = Carbon::createFromFormat('Y-m-d_H-i-s', $matches[1]);
                if ($backupDate->lt($cutoffDate)) {
                    File::deleteDirectory($dir);
                    $deletedCount++;
                }
            }
        }
        
        if ($deletedCount > 0) {
            $this->info("🗑️ Cleaned up {$deletedCount} old backup(s)");
        }
    }
    
    /**
     * Log backup completion
     */
    private function logBackupCompletion($backupFiles, $storageDestinations)
    {
        $totalSize = 0;
        foreach ($backupFiles as $file) {
            if (File::exists($file)) {
                $totalSize += File::size($file);
            }
        }
        
        Log::info('System backup completed', [
            'backup_name' => $this->backupName,
            'timestamp' => $this->timestamp,
            'type' => $this->option('type'),
            'files_count' => count($backupFiles),
            'total_size' => $totalSize,
            'storage_destinations' => $storageDestinations,
            'encrypted' => $this->option('encrypt'),
            'compressed' => $this->option('compress'),
        ]);
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