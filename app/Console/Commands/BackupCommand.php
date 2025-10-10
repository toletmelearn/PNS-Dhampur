<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ZipArchive;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create 
                            {--type=full : Backup type (full, database, files)}
                            {--compress : Compress backup files}
                            {--cleanup : Clean up old backups}
                            {--retention=30 : Backup retention days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create comprehensive system backups';

    protected string $backupPath;
    protected string $timestamp;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->backupPath = storage_path('backups');
        $this->timestamp = Carbon::now()->format('Y-m-d_H-i-s');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting backup process...');

        try {
            // Ensure backup directory exists
            if (!File::exists($this->backupPath)) {
                File::makeDirectory($this->backupPath, 0755, true);
            }

            $type = $this->option('type');
            $backupInfo = [
                'timestamp' => $this->timestamp,
                'type' => $type,
                'files' => [],
                'size' => 0,
            ];

            switch ($type) {
                case 'database':
                    $backupInfo = $this->backupDatabase($backupInfo);
                    break;
                case 'files':
                    $backupInfo = $this->backupFiles($backupInfo);
                    break;
                case 'full':
                default:
                    $backupInfo = $this->backupDatabase($backupInfo);
                    $backupInfo = $this->backupFiles($backupInfo);
                    break;
            }

            // Compress if requested
            if ($this->option('compress')) {
                $backupInfo = $this->compressBackup($backupInfo);
            }

            // Clean up old backups if requested
            if ($this->option('cleanup')) {
                $this->cleanupOldBackups();
            }

            // Save backup metadata
            $this->saveBackupMetadata($backupInfo);

            $this->info('Backup completed successfully!');
            $this->displayBackupInfo($backupInfo);

            Log::info('Backup created successfully', $backupInfo);

            return 0;

        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::error('Backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Backup database
     */
    protected function backupDatabase(array $backupInfo): array
    {
        $this->info('Backing up database...');

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($config['driver'] !== 'mysql') {
            throw new \Exception('Only MySQL databases are supported for backup');
        }

        $filename = "database_{$this->timestamp}.sql";
        $filepath = $this->backupPath . DIRECTORY_SEPARATOR . $filename;

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s --single-transaction --routines --triggers %s > %s',
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['database']),
            escapeshellarg($filepath)
        );

        // Execute backup
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database backup failed with return code: ' . $returnCode);
        }

        if (!File::exists($filepath) || File::size($filepath) === 0) {
            throw new \Exception('Database backup file was not created or is empty');
        }

        $backupInfo['files'][] = [
            'type' => 'database',
            'filename' => $filename,
            'path' => $filepath,
            'size' => File::size($filepath),
        ];

        $backupInfo['size'] += File::size($filepath);

        $this->info('Database backup completed: ' . $this->formatBytes(File::size($filepath)));

        return $backupInfo;
    }

    /**
     * Backup application files
     */
    protected function backupFiles(array $backupInfo): array
    {
        $this->info('Backing up application files...');

        $filesToBackup = [
            'storage' => storage_path(),
            'public_uploads' => public_path('uploads'),
            'env' => base_path('.env'),
            'config' => config_path(),
        ];

        foreach ($filesToBackup as $type => $path) {
            if (!File::exists($path)) {
                $this->warn("Path does not exist, skipping: {$path}");
                continue;
            }

            $filename = "{$type}_{$this->timestamp}";
            
            if (File::isDirectory($path)) {
                $filename .= '.tar';
                $backupPath = $this->backupPath . DIRECTORY_SEPARATOR . $filename;
                $this->createTarArchive($path, $backupPath);
            } else {
                $filename .= '.' . File::extension($path);
                $backupPath = $this->backupPath . DIRECTORY_SEPARATOR . $filename;
                File::copy($path, $backupPath);
            }

            if (File::exists($backupPath)) {
                $backupInfo['files'][] = [
                    'type' => $type,
                    'filename' => $filename,
                    'path' => $backupPath,
                    'size' => File::size($backupPath),
                ];

                $backupInfo['size'] += File::size($backupPath);
                $this->info("Backed up {$type}: " . $this->formatBytes(File::size($backupPath)));
            }
        }

        return $backupInfo;
    }

    /**
     * Create tar archive
     */
    protected function createTarArchive(string $sourcePath, string $archivePath): void
    {
        $command = sprintf(
            'tar -cf %s -C %s .',
            escapeshellarg($archivePath),
            escapeshellarg($sourcePath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception("Failed to create tar archive: {$archivePath}");
        }
    }

    /**
     * Compress backup files
     */
    protected function compressBackup(array $backupInfo): array
    {
        $this->info('Compressing backup files...');

        $zipFilename = "backup_{$this->timestamp}.zip";
        $zipPath = $this->backupPath . DIRECTORY_SEPARATOR . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create zip file: ' . $zipPath);
        }

        $totalOriginalSize = 0;
        foreach ($backupInfo['files'] as $file) {
            $zip->addFile($file['path'], $file['filename']);
            $totalOriginalSize += $file['size'];
            
            // Remove original file after adding to zip
            File::delete($file['path']);
        }

        $zip->close();

        $compressedSize = File::size($zipPath);
        $compressionRatio = round((1 - ($compressedSize / $totalOriginalSize)) * 100, 2);

        $backupInfo['files'] = [[
            'type' => 'compressed',
            'filename' => $zipFilename,
            'path' => $zipPath,
            'size' => $compressedSize,
            'original_size' => $totalOriginalSize,
            'compression_ratio' => $compressionRatio,
        ]];

        $backupInfo['size'] = $compressedSize;
        $backupInfo['compressed'] = true;

        $this->info("Compression completed. Saved {$compressionRatio}% space.");

        return $backupInfo;
    }

    /**
     * Clean up old backups
     */
    protected function cleanupOldBackups(): void
    {
        $retentionDays = (int) $this->option('retention');
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $this->info("Cleaning up backups older than {$retentionDays} days...");

        $files = File::files($this->backupPath);
        $deletedCount = 0;
        $deletedSize = 0;

        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(File::lastModified($file->getPathname()));
            
            if ($fileTime->lt($cutoffDate)) {
                $deletedSize += File::size($file->getPathname());
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Deleted {$deletedCount} old backup files, freed " . $this->formatBytes($deletedSize));
        } else {
            $this->info('No old backup files to clean up.');
        }
    }

    /**
     * Save backup metadata
     */
    protected function saveBackupMetadata(array $backupInfo): void
    {
        $metadataFile = $this->backupPath . DIRECTORY_SEPARATOR . "backup_{$this->timestamp}.json";
        File::put($metadataFile, json_encode($backupInfo, JSON_PRETTY_PRINT));
    }

    /**
     * Display backup information
     */
    protected function displayBackupInfo(array $backupInfo): void
    {
        $this->line('');
        $this->line('Backup Summary:');
        $this->line('===============');
        $this->line('Timestamp: ' . $backupInfo['timestamp']);
        $this->line('Type: ' . $backupInfo['type']);
        $this->line('Total Size: ' . $this->formatBytes($backupInfo['size']));
        $this->line('Files Created: ' . count($backupInfo['files']));

        if (isset($backupInfo['compressed']) && $backupInfo['compressed']) {
            $file = $backupInfo['files'][0];
            $this->line('Compression Ratio: ' . $file['compression_ratio'] . '%');
        }

        $this->line('');
        $this->line('Files:');
        foreach ($backupInfo['files'] as $file) {
            $this->line("  - {$file['filename']} ({$this->formatBytes($file['size'])})");
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}