<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseOptimizationService;
use Illuminate\Support\Facades\Log;

class DatabaseOptimizationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize 
                            {--analyze : Analyze database performance}
                            {--optimize : Optimize database tables}
                            {--repair : Repair database tables}
                            {--slow-queries : Show slow queries}
                            {--recommendations : Show optimization recommendations}
                            {--metrics : Show performance metrics}
                            {--tables=* : Specific tables to optimize/repair}
                            {--threshold=100 : Slow query threshold in milliseconds}
                            {--interactive : Run in interactive mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Database optimization and analysis tools';

    /**
     * Database optimization service
     *
     * @var DatabaseOptimizationService
     */
    private $optimizationService;

    /**
     * Create a new command instance.
     */
    public function __construct(DatabaseOptimizationService $optimizationService)
    {
        parent::__construct();
        $this->optimizationService = $optimizationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Database Optimization Tool');
        $this->info('============================');

        // Set slow query threshold
        $threshold = $this->option('threshold');
        $this->optimizationService->setSlowQueryThreshold($threshold);

        if ($this->option('interactive')) {
            return $this->runInteractiveMode();
        }

        $hasAction = false;

        if ($this->option('analyze')) {
            $this->analyzeDatabase();
            $hasAction = true;
        }

        if ($this->option('optimize')) {
            $this->optimizeTables();
            $hasAction = true;
        }

        if ($this->option('repair')) {
            $this->repairTables();
            $hasAction = true;
        }

        if ($this->option('slow-queries')) {
            $this->showSlowQueries();
            $hasAction = true;
        }

        if ($this->option('recommendations')) {
            $this->showRecommendations();
            $hasAction = true;
        }

        if ($this->option('metrics')) {
            $this->showMetrics();
            $hasAction = true;
        }

        if (!$hasAction) {
            $this->showHelp();
        }

        return 0;
    }

    /**
     * Run interactive mode
     */
    private function runInteractiveMode()
    {
        while (true) {
            $this->newLine();
            $choice = $this->choice(
                'What would you like to do?',
                [
                    'analyze' => 'Analyze database performance',
                    'optimize' => 'Optimize database tables',
                    'repair' => 'Repair database tables',
                    'slow-queries' => 'Show slow queries',
                    'recommendations' => 'Show optimization recommendations',
                    'metrics' => 'Show performance metrics',
                    'clear-cache' => 'Clear optimization cache',
                    'exit' => 'Exit'
                ],
                'analyze'
            );

            switch ($choice) {
                case 'analyze':
                    $this->analyzeDatabase();
                    break;
                case 'optimize':
                    $this->optimizeTables();
                    break;
                case 'repair':
                    $this->repairTables();
                    break;
                case 'slow-queries':
                    $this->showSlowQueries();
                    break;
                case 'recommendations':
                    $this->showRecommendations();
                    break;
                case 'metrics':
                    $this->showMetrics();
                    break;
                case 'clear-cache':
                    $this->clearCache();
                    break;
                case 'exit':
                    $this->info('👋 Goodbye!');
                    return 0;
            }

            if (!$this->confirm('Continue with another operation?', true)) {
                break;
            }
        }

        return 0;
    }

    /**
     * Analyze database performance
     */
    private function analyzeDatabase()
    {
        $this->info('🔍 Analyzing database performance...');
        
        $analysis = $this->optimizationService->analyzeTablePerformance();
        
        if (empty($analysis)) {
            $this->info('✅ No performance issues detected.');
            return;
        }

        $this->newLine();
        $this->warn('⚠️  Performance Issues Detected:');
        
        foreach ($analysis as $table => $data) {
            $this->newLine();
            $this->line("<fg=yellow>Table: {$table}</>");
            $this->line("Size: {$data['size_mb']} MB");
            $this->line("Rows: " . number_format($data['row_count']));
            $this->line("Fragmentation: {$data['fragmentation']}%");
            
            if (!empty($data['issues'])) {
                $this->line('<fg=red>Issues:</>');
                foreach ($data['issues'] as $issue) {
                    $this->line("  • {$issue}");
                }
            }
            
            if (!empty($data['suggestions'])) {
                $this->line('<fg=green>Suggestions:</>');
                foreach ($data['suggestions'] as $suggestion) {
                    $this->line("  • {$suggestion}");
                }
            }
        }
    }

    /**
     * Optimize database tables
     */
    private function optimizeTables()
    {
        $tables = $this->option('tables');
        
        if (empty($tables)) {
            if (!$this->confirm('Optimize all tables? This may take some time.', false)) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        $this->info('🔧 Optimizing database tables...');
        
        $bar = $this->output->createProgressBar();
        $bar->start();

        $results = $this->optimizationService->optimizeTables($tables ?: null);
        
        $bar->finish();
        $this->newLine(2);

        $this->displayTableResults($results, 'Optimization');
    }

    /**
     * Repair database tables
     */
    private function repairTables()
    {
        $tables = $this->option('tables');
        
        if (empty($tables)) {
            if (!$this->confirm('Repair all tables?', false)) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        $this->info('🔨 Repairing database tables...');
        
        $results = $this->optimizationService->repairTables($tables ?: null);
        
        $this->displayTableResults($results, 'Repair');
    }

    /**
     * Display table operation results
     */
    private function displayTableResults($results, $operation)
    {
        $successful = 0;
        $failed = 0;

        foreach ($results as $table => $result) {
            $status = $result['status'];
            $message = $result['message'];
            
            if ($status === 'OK' || $status === 'status') {
                $this->line("<fg=green>✅ {$table}: {$message}</>");
                $successful++;
            } else {
                $this->line("<fg=red>❌ {$table}: {$message}</>");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("{$operation} completed: {$successful} successful, {$failed} failed");
    }

    /**
     * Show slow queries
     */
    private function showSlowQueries()
    {
        $this->info('🐌 Analyzing slow queries...');
        
        $slowQueries = $this->optimizationService->getSlowQueries(20);
        
        if (empty($slowQueries)) {
            $this->info('✅ No slow queries detected.');
            return;
        }

        $this->newLine();
        $this->warn("⚠️  Found " . count($slowQueries) . " slow queries:");
        
        foreach ($slowQueries as $index => $query) {
            $this->newLine();
            $this->line("<fg=yellow>Query #" . ($index + 1) . "</>");
            $this->line("Average Time: {$query['avg_time']} ms");
            $this->line("Executions: " . number_format($query['execution_count']));
            $this->line("Total Time: {$query['total_time']} ms");
            $this->line("Max Time: {$query['max_time']} ms");
            $this->line("SQL: " . $this->truncateQuery($query['sql']));
        }

        // Show recorded slow queries from application
        $recordedQueries = $this->optimizationService->getRecordedSlowQueries();
        if (!empty($recordedQueries)) {
            $this->newLine();
            $this->warn("📝 Recent slow queries from application:");
            
            foreach (array_slice($recordedQueries, -5) as $index => $query) {
                $this->newLine();
                $this->line("<fg=cyan>Recent Query #" . ($index + 1) . "</>");
                $this->line("Time: {$query['time']} ms");
                $this->line("Timestamp: {$query['timestamp']}");
                $this->line("SQL: " . $this->truncateQuery($query['sql']));
            }
        }
    }

    /**
     * Show optimization recommendations
     */
    private function showRecommendations()
    {
        $this->info('💡 Generating optimization recommendations...');
        
        $recommendations = $this->optimizationService->generateOptimizationRecommendations();
        
        if (empty($recommendations)) {
            $this->info('✅ No optimization recommendations at this time.');
            return;
        }

        $this->newLine();
        $this->warn("📋 Optimization Recommendations:");
        
        foreach ($recommendations as $recommendation) {
            $this->newLine();
            
            $priority = $recommendation['priority'];
            $priorityColor = $priority === 'high' ? 'red' : ($priority === 'medium' ? 'yellow' : 'green');
            
            $this->line("<fg={$priorityColor}>Priority: " . strtoupper($priority) . "</>");
            $this->line("<fg=cyan>{$recommendation['title']}</>");
            $this->line("Description: {$recommendation['description']}");
            $this->line("<fg=green>Action: {$recommendation['action']}</>");
        }
    }

    /**
     * Show performance metrics
     */
    private function showMetrics()
    {
        $this->info('📊 Gathering performance metrics...');
        
        $metrics = $this->optimizationService->getPerformanceMetrics();
        
        if (empty($metrics)) {
            $this->error('❌ Unable to gather performance metrics.');
            return;
        }

        $this->newLine();
        $this->info('📈 Database Performance Metrics:');
        $this->newLine();

        $this->line("Total Connections: " . number_format($metrics['total_connections'] ?? 0));
        $this->line("Total Queries: " . number_format($metrics['total_queries'] ?? 0));
        $this->line("Slow Queries: " . number_format($metrics['slow_queries'] ?? 0));
        $this->line("Buffer Pool Reads: " . number_format($metrics['buffer_pool_reads'] ?? 0));
        
        if (isset($metrics['query_cache_hit_ratio'])) {
            $hitRatio = $metrics['query_cache_hit_ratio'];
            $color = $hitRatio >= 80 ? 'green' : ($hitRatio >= 60 ? 'yellow' : 'red');
            $this->line("<fg={$color}>Query Cache Hit Ratio: {$hitRatio}%</>");
        }
        
        if (isset($metrics['table_lock_contention'])) {
            $contention = $metrics['table_lock_contention'];
            $color = $contention <= 5 ? 'green' : ($contention <= 15 ? 'yellow' : 'red');
            $this->line("<fg={$color}>Table Lock Contention: {$contention}%</>");
        }
    }

    /**
     * Clear optimization cache
     */
    private function clearCache()
    {
        $this->info('🧹 Clearing optimization cache...');
        
        $this->optimizationService->clearCache();
        
        $this->info('✅ Cache cleared successfully.');
    }

    /**
     * Show help information
     */
    private function showHelp()
    {
        $this->info('Available options:');
        $this->line('  --analyze           Analyze database performance');
        $this->line('  --optimize          Optimize database tables');
        $this->line('  --repair            Repair database tables');
        $this->line('  --slow-queries      Show slow queries');
        $this->line('  --recommendations   Show optimization recommendations');
        $this->line('  --metrics           Show performance metrics');
        $this->line('  --tables=table1,table2  Specific tables to work with');
        $this->line('  --threshold=100     Slow query threshold in milliseconds');
        $this->line('  --interactive       Run in interactive mode');
        $this->newLine();
        $this->line('Examples:');
        $this->line('  php artisan db:optimize --analyze');
        $this->line('  php artisan db:optimize --optimize --tables=users,posts');
        $this->line('  php artisan db:optimize --interactive');
    }

    /**
     * Truncate long SQL queries for display
     */
    private function truncateQuery($sql, $length = 100)
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        
        if (strlen($sql) <= $length) {
            return $sql;
        }
        
        return substr($sql, 0, $length) . '...';
    }
}