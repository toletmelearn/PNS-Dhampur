<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Exception;

class BackupService
{
    /**
     * Backup configuration
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = config('backup');
    }

    /**
     * Create a full system backup
     */
    public function createFullBackup(array $options = [])
    {
        $options = array_merge([
            'encrypt' => true,
            'compress' => true,
            'verify' => true,
            'storage' => ['local'],
            'retention' => 30,
        ], $options);

        try {
            $this->notifyBackupStarted('full', $options);

            $backupInfo = [
                'type' => 'full',
                'timestamp' => Carbon::now(),
                'options' => $options,
                'files' => [],
                'size' => 0,
                'duration' => 0,
            ];

            $startTime = microtime(true);

            // Create database backup
            $dbBackup = $this->createDatabaseBackup($options);
            if ($dbBackup) {
                $backupInfo['files'][] = $dbBackup;
                $backupInfo['size'] += File::size($dbBackup);
            }

            // Create file system backup
            $fileBackup = $this->createFileSystemBackup($options);
            if ($fileBackup) {
                $backupInfo['files'][] = $fileBackup;
                $backupInfo['size'] += File::size($fileBackup);
            }

            // Process backups (compress, encrypt)
            $backupInfo['files'] = $this->processBackupFiles($backupInfo['files'], $options);

            // Upload to storage destinations
            foreach ($options['storage'] as $storage) {
                $this->uploadToStorage($backupInfo['files'], $storage);
            }

            // Verify backup integrity
            if ($options['verify']) {
                $this->verifyBackupIntegrity($backupInfo['files']);
            }

            $backupInfo['duration'] = microtime(true) - $startTime;

            $this->notifyBackupCompleted($backupInfo);
            $this->logBackupSuccess($backupInfo);

            return $backupInfo;

        } catch (Exception $e) {
            $this->notifyBackupFailed('full', $e->getMessage());
            $this->logBackupError('full', $e);
            throw $e;
        }
    }

    /**
     * Create database backup
     */
    public function createDatabaseBackup(array $options = [])
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = storage_path("app/backups/database_{$timestamp}.sql");

        // Ensure backup directory exists
        $backupDir = dirname($filename);
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        try {
            $dbConfig = config('database.connections.' . config('database.default'));
            
            // Get all tables
            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $dbConfig['database'];

            $sql = $this->generateDatabaseHeader($dbConfig);

            foreach ($tables as $table) {
                $tableName = $table->$tableKey;
                
                // Skip excluded tables
                if (in_array($tableName, $this->config['database']['connections']['mysql']['exclude_tables'] ?? [])) {
                    continue;
                }

                $sql .= $this->exportTable($tableName);
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            File::put($filename, $sql);

            return $filename;

        } catch (Exception $e) {
            Log::error('Database backup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create file system backup
     */
    public function createFileSystemBackup(array $options = [])
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = storage_path("app/backups/files_{$timestamp}.zip");

        try {
            $zip = new \ZipArchive();
            if ($zip->open($filename, \ZipArchive::CREATE) !== TRUE) {
                throw new Exception('Cannot create zip file');
            }

            $includePaths = $this->config['files']['include'] ?? [];
            $excludePaths = $this->config['files']['exclude'] ?? [];
            $excludeExtensions = $this->config['files']['exclude_extensions'] ?? [];
            $maxFileSize = $this->config['files']['max_file_size'] ?? (100 * 1024 * 1024);

            foreach ($includePaths as $path) {
                if (File::exists($path)) {
                    if (File::isDirectory($path)) {
                        $this->addDirectoryToZip($zip, $path, basename($path), $excludePaths, $excludeExtensions, $maxFileSize);
                    } else {
                        $zip->addFile($path, basename($path));
                    }
                }
            }

            $zip->close();

            return $filename;

        } catch (Exception $e) {
            Log::error('File system backup failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Process backup files (compress, encrypt)
     */
    private function processBackupFiles(array $files, array $options)
    {
        $processedFiles = [];

        foreach ($files as $file) {
            $currentFile = $file;

            // Compress if requested
            if ($options['compress'] && !str_ends_with($file, '.zip')) {
                $compressedFile = $this->compressFile($currentFile);
                if ($compressedFile) {
                    File::delete($currentFile);
                    $currentFile = $compressedFile;
                }
            }

            // Encrypt if requested
            if ($options['encrypt']) {
                $encryptedFile = $this->encryptFile($currentFile);
                if ($encryptedFile) {
                    File::delete($currentFile);
                    $currentFile = $encryptedFile;
                }
            }

            $processedFiles[] = $currentFile;
        }

        return $processedFiles;
    }

    /**
     * Upload backup to storage destination
     */
    private function uploadToStorage(array $files, string $storage)
    {
        $storageConfig = $this->config['storage']['destinations'][$storage] ?? null;
        
        if (!$storageConfig || !$storageConfig['enabled']) {
            return;
        }

        try {
            switch ($storage) {
                case 'local':
                    // Files are already in local storage
                    break;

                case 's3':
                    foreach ($files as $file) {
                        $filename = 'backups/' . basename($file);
                        Storage::disk('s3')->put($filename, File::get($file));
                    }
                    break;

                case 'ftp':
                    foreach ($files as $file) {
                        $filename = 'backups/' . basename($file);
                        Storage::disk('ftp')->put($filename, File::get($file));
                    }
                    break;

                default:
                    Log::warning("Unknown storage destination: {$storage}");
            }

        } catch (Exception $e) {
            Log::error("Upload to {$storage} failed", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Verify backup integrity
     */
    private function verifyBackupIntegrity(array $files)
    {
        foreach ($files as $file) {
            if (!File::exists($file)) {
                throw new Exception("Backup file not found: {$file}");
            }

            $size = File::size($file);
            if ($size === 0) {
                throw new Exception("Backup file is empty: {$file}");
            }

            // Generate and store checksum
            $checksum = hash_file('sha256', $file);
            $checksumFile = $file . '.sha256';
            File::put($checksumFile, $checksum);

            Log::info('Backup file verified', [
                'file' => basename($file),
                'size' => $size,
                'checksum' => substr($checksum, 0, 16) . '...'
            ]);
        }
    }

    /**
     * Clean up old backups
     */
    public function cleanupOldBackups(int $retentionDays = null)
    {
        $retentionDays = $retentionDays ?? $this->config['default']['retention_days'];
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            return 0;
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
            Log::info("Backup cleanup completed: {$deletedCount} old backups removed");
        }

        return $deletedCount;
    }

    /**
     * Get backup statistics
     */
    public function getBackupStatistics()
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            return [
                'total_backups' => 0,
                'total_size' => 0,
                'latest_backup' => null,
                'oldest_backup' => null,
            ];
        }

        $directories = File::directories($backupDir);
        $totalSize = 0;
        $backupDates = [];

        foreach ($directories as $dir) {
            $dirName = basename($dir);
            if (preg_match('/backup_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $dirName, $matches)) {
                $backupDate = Carbon::createFromFormat('Y-m-d_H-i-s', $matches[1]);
                $backupDates[] = $backupDate;

                // Calculate directory size
                $files = File::allFiles($dir);
                foreach ($files as $file) {
                    $totalSize += $file->getSize();
                }
            }
        }

        sort($backupDates);

        return [
            'total_backups' => count($backupDates),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'latest_backup' => end($backupDates),
            'oldest_backup' => reset($backupDates),
        ];
    }

    /**
     * Generate database header
     */
    private function generateDatabaseHeader($dbConfig)
    {
        return "-- Database Backup\n" .
               "-- Generated on: " . Carbon::now()->toDateTimeString() . "\n" .
               "-- Database: " . $dbConfig['database'] . "\n" .
               "-- Host: " . $dbConfig['host'] . "\n\n" .
               "SET FOREIGN_KEY_CHECKS=0;\n" .
               "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n" .
               "SET AUTOCOMMIT = 0;\n" .
               "START TRANSACTION;\n\n";
    }

    /**
     * Export table structure and data
     */
    private function exportTable($tableName)
    {
        $sql = "-- Table structure for `{$tableName}`\n";
        $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";

        // Get table structure
        $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
        $sql .= $createTable->{'Create Table'} . ";\n\n";

        // Get table data
        $rows = DB::table($tableName)->get();
        if ($rows->count() > 0) {
            $sql .= "-- Data for table `{$tableName}`\n";
            $sql .= "LOCK TABLES `{$tableName}` WRITE;\n";
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

            $sql .= implode(",\n", $values) . ";\n";
            $sql .= "UNLOCK TABLES;\n\n";
        }

        return $sql;
    }

    /**
     * Add directory to zip recursively
     */
    private function addDirectoryToZip($zip, $dir, $relativePath, $excludePaths, $excludeExtensions, $maxFileSize)
    {
        $files = File::allFiles($dir);

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativeFilePath = $relativePath . '/' . $file->getRelativePathname();

            // Check if file should be excluded
            if ($this->shouldExcludeFile($filePath, $excludePaths, $excludeExtensions, $maxFileSize)) {
                continue;
            }

            $zip->addFile($filePath, $relativeFilePath);
        }
    }

    /**
     * Check if file should be excluded
     */
    private function shouldExcludeFile($filePath, $excludePaths, $excludeExtensions, $maxFileSize)
    {
        // Check file size
        if (File::size($filePath) > $maxFileSize) {
            return true;
        }

        // Check excluded paths
        foreach ($excludePaths as $excludePath) {
            if (str_contains($filePath, $excludePath)) {
                return true;
            }
        }

        // Check excluded extensions
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        if (in_array($extension, $excludeExtensions)) {
            return true;
        }

        return false;
    }

    /**
     * Compress file
     */
    private function compressFile($file)
    {
        $compressedFile = $file . '.gz';

        try {
            $data = File::get($file);
            $compressed = gzencode($data, $this->config['compression']['level']);
            File::put($compressedFile, $compressed);

            return $compressedFile;

        } catch (Exception $e) {
            Log::error('File compression failed', ['file' => $file, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Encrypt file
     */
    private function encryptFile($file)
    {
        $encryptedFile = $file . '.enc';

        try {
            $key = $this->config['encryption']['key'];
            $iv = random_bytes(16);

            $data = File::get($file);
            $encrypted = openssl_encrypt($data, $this->config['encryption']['cipher'], $key, 0, $iv);

            // Prepend IV to encrypted data
            $encryptedData = base64_encode($iv . $encrypted);
            File::put($encryptedFile, $encryptedData);

            return $encryptedFile;

        } catch (Exception $e) {
            Log::error('File encryption failed', ['file' => $file, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Send backup started notification
     */
    private function notifyBackupStarted($type, $options)
    {
        if (!$this->config['monitoring']['notifications']['events']['backup_started']) {
            return;
        }

        Log::info('Backup started', ['type' => $type, 'options' => $options]);
    }

    /**
     * Send backup completed notification
     */
    private function notifyBackupCompleted($backupInfo)
    {
        if (!$this->config['monitoring']['notifications']['events']['backup_completed']) {
            return;
        }

        $message = "Backup completed successfully!\n" .
                   "Type: {$backupInfo['type']}\n" .
                   "Files: " . count($backupInfo['files']) . "\n" .
                   "Size: " . $this->formatBytes($backupInfo['size']) . "\n" .
                   "Duration: " . round($backupInfo['duration'], 2) . " seconds";

        Log::info('Backup completed', $backupInfo);
    }

    /**
     * Send backup failed notification
     */
    private function notifyBackupFailed($type, $error)
    {
        if (!$this->config['monitoring']['notifications']['events']['backup_failed']) {
            return;
        }

        Log::error('Backup failed', ['type' => $type, 'error' => $error]);
    }

    /**
     * Log backup success
     */
    private function logBackupSuccess($backupInfo)
    {
        Log::info('System backup completed successfully', $backupInfo);
    }

    /**
     * Log backup error
     */
    private function logBackupError($type, Exception $e)
    {
        Log::error('System backup failed', [
            'type' => $type,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
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