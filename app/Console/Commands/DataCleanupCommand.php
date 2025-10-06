<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataCleanupService;
use Illuminate\Support\Facades\Log;

class DataCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:cleanup 
                            {--type=all : Type of cleanup (orphaned, duplicates, consistency, archive, all)}
                            {--action=report : Action to perform (report, fix, merge)}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform data cleanup operations on the database';

    /**
     * Data cleanup service instance
     */
    protected $cleanupService;

    /**
     * Create a new command instance.
     */
    public function __construct(DataCleanupService $cleanupService)
    {
        parent::__construct();
        $this->cleanupService = $cleanupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $action = $this->option('action');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Starting data cleanup process...');
        $this->info("Type: {$type}, Action: {$action}");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        switch ($type) {
            case 'orphaned':
                $this->handleOrphanedRecords($action, $dryRun, $force);
                break;
            case 'duplicates':
                $this->handleDuplicates($action, $dryRun, $force);
                break;
            case 'consistency':
                $this->handleConsistencyChecks($action, $dryRun, $force);
                break;
            case 'archive':
                $this->handleArchiving($action, $dryRun, $force);
                break;
            case 'all':
                $this->handleAllCleanup($action, $dryRun, $force);
                break;
            default:
                $this->error('Invalid cleanup type. Use: orphaned, duplicates, consistency, archive, or all');
                return 1;
        }

        $this->info('Data cleanup process completed.');
        return 0;
    }

    /**
     * Handle orphaned records cleanup
     */
    protected function handleOrphanedRecords($action, $dryRun, $force)
    {
        $this->info('Checking for orphaned student records...');
        
        $orphanedData = $this->cleanupService->findOrphanedStudents();
        
        if ($orphanedData['total_issues'] === 0) {
            $this->info('No orphaned student records found.');
            return;
        }

        $this->warn("Found {$orphanedData['total_issues']} orphaned student records:");
        $this->warn("- {$orphanedData['orphaned_students']->count()} students with invalid class references");
        $this->warn("- {$orphanedData['students_without_class']->count()} active students without class assignment");

        if ($action === 'report') {
            $this->displayOrphanedStudentsTable($orphanedData);
            return;
        }

        if ($action === 'fix') {
            if ($dryRun) {
                $this->info('Would fix orphaned records by setting them as inactive');
                return;
            }

            if (!$force && !$this->confirm('Do you want to fix these orphaned records by setting them as inactive?')) {
                $this->info('Operation cancelled.');
                return;
            }

            $result = $this->cleanupService->fixOrphanedStudents('set_inactive');
            
            if ($result['success']) {
                $this->info("Successfully fixed {$result['fixed_count']} orphaned student records.");
            } else {
                $this->error("Error fixing orphaned records: {$result['error']}");
            }
        }
    }

    /**
     * Handle duplicate records
     */
    protected function handleDuplicates($action, $dryRun, $force)
    {
        $this->info('Checking for duplicate student records...');
        
        $duplicates = $this->cleanupService->findDuplicateStudents();
        
        $totalDuplicates = 0;
        foreach ($duplicates as $type => $typeData) {
            $totalDuplicates += count($typeData);
        }

        if ($totalDuplicates === 0) {
            $this->info('No duplicate student records found.');
            return;
        }

        $this->warn("Found {$totalDuplicates} sets of duplicate records:");
        
        foreach ($duplicates as $type => $typeData) {
            $this->warn("- " . count($typeData) . " duplicate sets by {$type}");
        }

        if ($action === 'report') {
            $this->displayDuplicatesTable($duplicates);
            return;
        }

        if ($action === 'merge') {
            $this->info('Interactive duplicate merging is not available in command line.');
            $this->info('Please use the admin interface for merging duplicates.');
        }
    }

    /**
     * Handle data consistency checks
     */
    protected function handleConsistencyChecks($action, $dryRun, $force)
    {
        $this->info('Performing data consistency checks...');
        
        $issues = $this->cleanupService->performDataConsistencyChecks();
        
        if (empty($issues)) {
            $this->info('No data consistency issues found.');
            return;
        }

        $totalIssues = array_sum(array_column($issues, 'count'));
        $this->warn("Found {$totalIssues} data consistency issues:");
        
        foreach ($issues as $type => $data) {
            $this->warn("- {$data['count']} {$type}");
        }

        if ($action === 'report') {
            $this->displayConsistencyIssuesTable($issues);
            return;
        }

        if ($action === 'fix') {
            if ($dryRun) {
                $this->info('Would fix all consistency issues');
                return;
            }

            if (!$force && !$this->confirm('Do you want to fix these consistency issues?')) {
                $this->info('Operation cancelled.');
                return;
            }

            $result = $this->cleanupService->fixDataConsistencyIssues($issues);
            
            if ($result['success']) {
                $this->info("Successfully fixed {$result['fixed_count']} consistency issues.");
            } else {
                $this->error("Error fixing consistency issues: {$result['error']}");
            }
        }
    }

    /**
     * Handle data archiving
     */
    protected function handleArchiving($action, $dryRun, $force)
    {
        $this->info('Checking for old data to archive...');
        
        if ($action === 'report') {
            $this->info('Archive report functionality not implemented yet.');
            return;
        }

        if ($action === 'fix') {
            if ($dryRun) {
                $this->info('Would archive old data based on default criteria');
                return;
            }

            if (!$force && !$this->confirm('Do you want to archive old data?')) {
                $this->info('Operation cancelled.');
                return;
            }

            $result = $this->cleanupService->archiveOldData();
            
            if ($result['success']) {
                $this->info("Successfully archived {$result['archived_count']} old records.");
            } else {
                $this->error("Error archiving data: {$result['error']}");
            }
        }
    }

    /**
     * Handle all cleanup operations
     */
    protected function handleAllCleanup($action, $dryRun, $force)
    {
        $this->info('Performing comprehensive data cleanup...');
        
        if ($action === 'report') {
            $report = $this->cleanupService->getCleanupReport();
            $this->displayComprehensiveReport($report);
            return;
        }

        // Run all cleanup operations
        $this->handleOrphanedRecords($action, $dryRun, $force);
        $this->line('');
        $this->handleConsistencyChecks($action, $dryRun, $force);
        $this->line('');
        $this->handleDuplicates($action, $dryRun, $force);
    }

    /**
     * Display orphaned students table
     */
    protected function displayOrphanedStudentsTable($orphanedData)
    {
        if ($orphanedData['orphaned_students']->count() > 0) {
            $this->info('Students with invalid class references:');
            $headers = ['ID', 'Name', 'Admission No', 'Class ID', 'Status'];
            $rows = [];
            
            foreach ($orphanedData['orphaned_students'] as $student) {
                $rows[] = [
                    $student->id,
                    $student->name,
                    $student->admission_no,
                    $student->class_id,
                    $student->status
                ];
            }
            
            $this->table($headers, $rows);
        }

        if ($orphanedData['students_without_class']->count() > 0) {
            $this->info('Active students without class assignment:');
            $headers = ['ID', 'Name', 'Admission No', 'Status'];
            $rows = [];
            
            foreach ($orphanedData['students_without_class'] as $student) {
                $rows[] = [
                    $student->id,
                    $student->name,
                    $student->admission_no,
                    $student->status
                ];
            }
            
            $this->table($headers, $rows);
        }
    }

    /**
     * Display duplicates table
     */
    protected function displayDuplicatesTable($duplicates)
    {
        foreach ($duplicates as $type => $typeData) {
            $this->info("Duplicates by {$type}:");
            $headers = ['Field Value', 'Count', 'Student IDs', 'Names'];
            $rows = [];
            
            foreach ($typeData as $duplicate) {
                $studentIds = $duplicate['students']->pluck('id')->implode(', ');
                $names = $duplicate['students']->pluck('name')->implode(', ');
                
                $rows[] = [
                    $duplicate['value'],
                    $duplicate['count'],
                    $studentIds,
                    $names
                ];
            }
            
            $this->table($headers, $rows);
            $this->line('');
        }
    }

    /**
     * Display consistency issues table
     */
    protected function displayConsistencyIssuesTable($issues)
    {
        foreach ($issues as $type => $data) {
            $this->info("Issue: {$type} ({$data['count']} records)");
            
            if ($data['count'] <= 10) {
                // Show details for small number of issues
                $this->info('Sample records:');
                foreach ($data['records']->take(5) as $record) {
                    $this->line("- ID: {$record->id}");
                }
            }
            $this->line('');
        }
    }

    /**
     * Display comprehensive cleanup report
     */
    protected function displayComprehensiveReport($report)
    {
        $this->info('=== COMPREHENSIVE DATA CLEANUP REPORT ===');
        $this->info('Generated: ' . $report['timestamp']->format('Y-m-d H:i:s'));
        $this->line('');

        // Statistics
        $this->info('=== DATABASE STATISTICS ===');
        $stats = $report['statistics'];
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Students', $stats['total_students']],
                ['Active Students', $stats['active_students']],
                ['Inactive Students', $stats['inactive_students']],
                ['Students with Class', $stats['students_with_class']],
                ['Students without Class', $stats['students_without_class']],
                ['Total Classes', $stats['total_classes']],
                ['Active Classes', $stats['active_classes']],
            ]
        );
        $this->line('');

        // Orphaned records
        $orphaned = $report['orphaned_students'];
        $this->info('=== ORPHANED RECORDS ===');
        $this->info("Total Issues: {$orphaned['total_issues']}");
        $this->info("- Students with invalid class: {$orphaned['orphaned_students']->count()}");
        $this->info("- Students without class: {$orphaned['students_without_class']->count()}");
        $this->line('');

        // Duplicates
        $duplicates = $report['duplicate_students'];
        $this->info('=== DUPLICATE RECORDS ===');
        foreach ($duplicates as $type => $typeData) {
            $this->info("- Duplicates by {$type}: " . count($typeData));
        }
        $this->line('');

        // Consistency issues
        $issues = $report['consistency_issues'];
        $this->info('=== CONSISTENCY ISSUES ===');
        if (empty($issues)) {
            $this->info('No consistency issues found.');
        } else {
            foreach ($issues as $type => $data) {
                $this->info("- {$type}: {$data['count']} records");
            }
        }
    }
}