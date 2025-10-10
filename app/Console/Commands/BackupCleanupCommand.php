<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BackupService;
use Carbon\Carbon;

class BackupCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:cleanup 
                            {--retention-days= : Number of days to retain backups (overrides config)}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old backup files based on retention policy';

    /**
     * Backup service instance
     */
    protected BackupService $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting backup cleanup process...');

        try {
            $retentionDays = $this->option('retention-days') 
                ? (int) $this->option('retention-days')
                : config('backup.default.retention_days', 30);

            $dryRun = $this->option('dry-run');
            $force = $this->option('force');

            $this->info("Retention policy: {$retentionDays} days");
            $this->info("Cutoff date: " . Carbon::now()->subDays($retentionDays)->format('Y-m-d H:i:s'));

            if ($dryRun) {
                $this->warn('DRY RUN MODE - No files will be deleted');
            }

            // Get cleanup results
            if ($dryRun) {
                $result = $this->getDryRunResults($retentionDays);
            } else {
                if (!$force && !$this->confirm('Are you sure you want to delete old backup files?')) {
                    $this->info('Cleanup cancelled.');
                    return 0;
                }

                $result = $this->backupService->cleanupOldBackups($retentionDays);
            }

            // Display results
            $this->displayResults($result, $dryRun);

            if (!$dryRun && $result['deleted_count'] > 0) {
                $this->info('Backup cleanup completed successfully.');
            } elseif ($dryRun) {
                $this->info('Dry run completed. Use --force to perform actual cleanup.');
            } else {
                $this->info('No old backups found to clean up.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Backup cleanup failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Get dry run results without deleting files
     */
    protected function getDryRunResults(int $retentionDays): array
    {
        $backupPath = storage_path('backups');
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        if (!is_dir($backupPath)) {
            return [
                'deleted_count' => 0,
                'deleted_size' => 0,
                'deleted_files' => [],
            ];
        }

        $files = glob($backupPath . DIRECTORY_SEPARATOR . '*');
        $filesToDelete = [];
        $totalSize = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $fileTime = Carbon::createFromTimestamp(filemtime($file));
                
                if ($fileTime->lt($cutoffDate)) {
                    $size = filesize($file);
                    $filesToDelete[] = basename($file);
                    $totalSize += $size;
                }
            }
        }

        return [
            'deleted_count' => count($filesToDelete),
            'deleted_size' => $totalSize,
            'deleted_files' => $filesToDelete,
        ];
    }

    /**
     * Display cleanup results
     */
    protected function displayResults(array $result, bool $dryRun = false): void
    {
        $action = $dryRun ? 'Would delete' : 'Deleted';

        $this->table(
            ['Metric', 'Value'],
            [
                ['Files ' . strtolower($action), $result['deleted_count']],
                ['Space freed', $this->formatBytes($result['deleted_size'])],
            ]
        );

        if ($result['deleted_count'] > 0 && $this->option('verbose')) {
            $this->info("\n{$action} files:");
            foreach ($result['deleted_files'] as $file) {
                $this->line("  - {$file}");
            }
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