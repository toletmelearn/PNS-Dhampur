<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ZipArchive;

class RestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore 
                            {backup? : Backup file or timestamp to restore}
                            {--list : List available backups}
                            {--type=full : Restore type (full, database, files)}
                            {--force : Force restore without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore system from backups';

    protected string $backupPath;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->backupPath = storage_path('backups');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            if ($this->option('list')) {
                return $this->listBackups();
            }

            $backup = $this->argument('backup');
            if (!$backup) {
                $backup = $this->selectBackup();
                if (!$backup) {
                    $this->error('No backup selected.');
                    return 1;
                }
            }

            $backupInfo = $this->getBackupInfo($backup);
            if (!$backupInfo) {
                $this->error('Backup not found or invalid.');
                return 1;
            }

            $this->displayBackupInfo($backupInfo);

            if (!$this->option('force') && !$this->confirm('Are you sure you want to restore this backup? This will overwrite current data.')) {
                $this->info('Restore cancelled.');
                return 0;
            }

            $this->info('Starting restore process...');

            $type = $this->option('type');
            switch ($type) {
                case 'database':
                    $this->restoreDatabase($backupInfo);
                    break;
                case 'files':
                    $this->restoreFiles($backupInfo);
                    break;
                case 'full':
                default:
                    $this->restoreDatabase($backupInfo);
                    $this->restoreFiles($backupInfo);
                    break;
            }

            $this->info('Restore completed successfully!');
            Log::info('Backup restored successfully', ['backup' => $backup, 'type' => $type]);

            return 0;

        } catch (\Exception $e) {
            $this->error('Restore failed: ' . $e->getMessage());
            Log::error('Restore failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * List available backups
     */
    protected function listBackups(): int
    {
        if (!File::exists($this->backupPath)) {
            $this->info('No backups directory found.');
            return 0;
        }

        $backups = $this->getAvailableBackups();

        if (empty($backups)) {
            $this->info('No backups found.');
            return 0;
        }

        $this->line('Available Backups:');
        $this->line('==================');

        $headers = ['Timestamp', 'Type', 'Size', 'Files', 'Compressed'];
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                $backup['timestamp'],
                $backup['type'],
                $this->formatBytes($backup['size']),
                count($backup['files']),
                isset($backup['compressed']) && $backup['compressed'] ? 'Yes' : 'No',
            ];
        }

        $this->table($headers, $rows);

        return 0;
    }

    /**
     * Select backup interactively
     */
    protected function selectBackup(): ?string
    {
        $backups = $this->getAvailableBackups();

        if (empty($backups)) {
            $this->error('No backups available.');
            return null;
        }

        $choices = [];
        foreach ($backups as $backup) {
            $choices[] = $backup['timestamp'] . ' (' . $backup['type'] . ', ' . $this->formatBytes($backup['size']) . ')';
        }

        $selected = $this->choice('Select a backup to restore:', $choices);
        
        // Extract timestamp from selection
        return explode(' ', $selected)[0];
    }

    /**
     * Get available backups
     */
    protected function getAvailableBackups(): array
    {
        $backups = [];
        $metadataFiles = File::glob($this->backupPath . DIRECTORY_SEPARATOR . 'backup_*.json');

        foreach ($metadataFiles as $file) {
            $content = File::get($file);
            $backup = json_decode($content, true);
            
            if ($backup && isset($backup['timestamp'])) {
                $backups[] = $backup;
            }
        }

        // Sort by timestamp descending
        usort($backups, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return $backups;
    }

    /**
     * Get backup information
     */
    protected function getBackupInfo(string $backup): ?array
    {
        // Try to find by timestamp
        $metadataFile = $this->backupPath . DIRECTORY_SEPARATOR . "backup_{$backup}.json";
        
        if (File::exists($metadataFile)) {
            $content = File::get($metadataFile);
            return json_decode($content, true);
        }

        // Try to find by filename
        if (File::exists($backup)) {
            // If it's a direct file path, try to extract info
            $filename = basename($backup);
            if (preg_match('/backup_(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})/', $filename, $matches)) {
                $timestamp = $matches[1];
                $metadataFile = $this->backupPath . DIRECTORY_SEPARATOR . "backup_{$timestamp}.json";
                
                if (File::exists($metadataFile)) {
                    $content = File::get($metadataFile);
                    return json_decode($content, true);
                }
            }
        }

        return null;
    }

    /**
     * Display backup information
     */
    protected function displayBackupInfo(array $backupInfo): void
    {
        $this->line('');
        $this->line('Backup Information:');
        $this->line('===================');
        $this->line('Timestamp: ' . $backupInfo['timestamp']);
        $this->line('Type: ' . $backupInfo['type']);
        $this->line('Total Size: ' . $this->formatBytes($backupInfo['size']));
        $this->line('Files: ' . count($backupInfo['files']));

        if (isset($backupInfo['compressed']) && $backupInfo['compressed']) {
            $this->line('Compressed: Yes');
        }

        $this->line('');
    }

    /**
     * Restore database
     */
    protected function restoreDatabase(array $backupInfo): void
    {
        $this->info('Restoring database...');

        $databaseFile = null;
        
        // Find database file in backup
        foreach ($backupInfo['files'] as $file) {
            if ($file['type'] === 'database' || str_contains($file['filename'], 'database_')) {
                $databaseFile = $file;
                break;
            }
        }

        if (!$databaseFile) {
            // Check if it's a compressed backup
            if (isset($backupInfo['compressed']) && $backupInfo['compressed']) {
                $this->extractCompressedBackup($backupInfo);
                
                // Look for database file in extracted files
                $extractedFiles = File::files($this->backupPath . DIRECTORY_SEPARATOR . 'temp_restore');
                foreach ($extractedFiles as $file) {
                    if (str_contains($file->getFilename(), 'database_') && str_ends_with($file->getFilename(), '.sql')) {
                        $databaseFile = [
                            'path' => $file->getPathname(),
                            'filename' => $file->getFilename(),
                        ];
                        break;
                    }
                }
            }
        }

        if (!$databaseFile) {
            throw new \Exception('No database backup found in the selected backup.');
        }

        if (!File::exists($databaseFile['path'])) {
            throw new \Exception('Database backup file not found: ' . $databaseFile['path']);
        }

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($config['driver'] !== 'mysql') {
            throw new \Exception('Only MySQL databases are supported for restore');
        }

        // Build mysql restore command
        $command = sprintf(
            'mysql --user=%s --password=%s --host=%s --port=%s %s < %s',
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['database']),
            escapeshellarg($databaseFile['path'])
        );

        // Execute restore
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Database restore failed with return code: ' . $returnCode);
        }

        $this->info('Database restored successfully.');
    }

    /**
     * Restore files
     */
    protected function restoreFiles(array $backupInfo): void
    {
        $this->info('Restoring files...');

        if (isset($backupInfo['compressed']) && $backupInfo['compressed']) {
            $this->extractCompressedBackup($backupInfo);
            $extractPath = $this->backupPath . DIRECTORY_SEPARATOR . 'temp_restore';
        } else {
            $extractPath = $this->backupPath;
        }

        $filesToRestore = [
            'storage' => storage_path(),
            'public_uploads' => public_path('uploads'),
            'env' => base_path('.env'),
            'config' => config_path(),
        ];

        foreach ($filesToRestore as $type => $targetPath) {
            $backupFile = null;
            
            // Find the backup file for this type
            if (isset($backupInfo['compressed']) && $backupInfo['compressed']) {
                $files = File::files($extractPath);
                foreach ($files as $file) {
                    if (str_contains($file->getFilename(), $type . '_')) {
                        $backupFile = $file->getPathname();
                        break;
                    }
                }
            } else {
                foreach ($backupInfo['files'] as $file) {
                    if ($file['type'] === $type) {
                        $backupFile = $file['path'];
                        break;
                    }
                }
            }

            if (!$backupFile || !File::exists($backupFile)) {
                $this->warn("Backup file for {$type} not found, skipping.");
                continue;
            }

            // Create backup of current files before restore
            if (File::exists($targetPath)) {
                $backupCurrentPath = $targetPath . '.backup.' . Carbon::now()->format('Y-m-d_H-i-s');
                if (File::isDirectory($targetPath)) {
                    File::copyDirectory($targetPath, $backupCurrentPath);
                } else {
                    File::copy($targetPath, $backupCurrentPath);
                }
                $this->info("Current {$type} backed up to: {$backupCurrentPath}");
            }

            // Restore the file/directory
            if (str_ends_with($backupFile, '.tar')) {
                // Extract tar archive
                if (File::isDirectory($targetPath)) {
                    File::deleteDirectory($targetPath);
                }
                File::makeDirectory($targetPath, 0755, true);
                
                $command = sprintf(
                    'tar -xf %s -C %s',
                    escapeshellarg($backupFile),
                    escapeshellarg($targetPath)
                );
                
                $output = [];
                $returnCode = 0;
                exec($command, $output, $returnCode);
                
                if ($returnCode !== 0) {
                    throw new \Exception("Failed to extract tar archive: {$backupFile}");
                }
            } else {
                // Copy single file
                File::copy($backupFile, $targetPath);
            }

            $this->info("Restored {$type} successfully.");
        }

        // Clean up temporary extraction directory
        if (isset($extractPath) && $extractPath !== $this->backupPath) {
            File::deleteDirectory($extractPath);
        }
    }

    /**
     * Extract compressed backup
     */
    protected function extractCompressedBackup(array $backupInfo): void
    {
        $compressedFile = $backupInfo['files'][0]['path'];
        $extractPath = $this->backupPath . DIRECTORY_SEPARATOR . 'temp_restore';

        if (File::exists($extractPath)) {
            File::deleteDirectory($extractPath);
        }
        File::makeDirectory($extractPath, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($compressedFile) !== TRUE) {
            throw new \Exception('Cannot open compressed backup file: ' . $compressedFile);
        }

        if (!$zip->extractTo($extractPath)) {
            throw new \Exception('Failed to extract compressed backup.');
        }

        $zip->close();
        $this->info('Compressed backup extracted successfully.');
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