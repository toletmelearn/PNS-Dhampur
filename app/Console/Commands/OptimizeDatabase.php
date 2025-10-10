<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:optimize 
                            {--analyze : Analyze table statistics}
                            {--optimize : Optimize table storage}
                            {--repair : Repair corrupted tables}
                            {--check-indexes : Check for missing indexes}
                            {--vacuum : Vacuum database (SQLite only)}
                            {--all : Run all optimization tasks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database performance by analyzing tables, updating statistics, and managing indexes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting database optimization...');
        $startTime = microtime(true);

        try {
            if ($this->option('all')) {
                $this->runAllOptimizations();
            } else {
                if ($this->option('analyze')) {
                    $this->analyzeTables();
                }

                if ($this->option('optimize')) {
                    $this->optimizeTables();
                }

                if ($this->option('repair')) {
                    $this->repairTables();
                }

                if ($this->option('check-indexes')) {
                    $this->checkIndexes();
                }

                if ($this->option('vacuum')) {
                    $this->vacuumDatabase();
                }

                // If no specific options, run basic optimization
                if (!$this->option('analyze') && !$this->option('optimize') && 
                    !$this->option('repair') && !$this->option('check-indexes') && 
                    !$this->option('vacuum')) {
                    $this->runBasicOptimization();
                }
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            $this->info("Database optimization completed in {$duration} seconds.");
            Log::info('Database optimization completed', ['duration' => $duration]);

        } catch (\Exception $e) {
            $this->error('Database optimization failed: ' . $e->getMessage());
            Log::error('Database optimization failed', ['error' => $e->getMessage()]);
            return 1;
        }

        return 0;
    }

    /**
     * Run all optimization tasks
     */
    protected function runAllOptimizations()
    {
        $this->info('Running all optimization tasks...');
        
        $this->analyzeTables();
        $this->optimizeTables();
        $this->checkIndexes();
        
        if (DB::getDriverName() === 'sqlite') {
            $this->vacuumDatabase();
        }
    }

    /**
     * Run basic optimization tasks
     */
    protected function runBasicOptimization()
    {
        $this->info('Running basic optimization...');
        
        $this->analyzeTables();
        $this->optimizeTables();
    }

    /**
     * Analyze table statistics
     */
    protected function analyzeTables()
    {
        $this->info('Analyzing table statistics...');
        
        $driver = DB::getDriverName();
        $tables = $this->getAllTables();
        
        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        foreach ($tables as $table) {
            try {
                switch ($driver) {
                    case 'mysql':
                        DB::statement("ANALYZE TABLE `{$table}`");
                        break;
                    case 'pgsql':
                        DB::statement("ANALYZE \"{$table}\"");
                        break;
                    case 'sqlite':
                        DB::statement("ANALYZE `{$table}`");
                        break;
                }
                $bar->advance();
            } catch (\Exception $e) {
                $this->warn("Failed to analyze table {$table}: " . $e->getMessage());
                Log::warning("Failed to analyze table {$table}", ['error' => $e->getMessage()]);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Table analysis completed.');
    }

    /**
     * Optimize table storage
     */
    protected function optimizeTables()
    {
        $this->info('Optimizing table storage...');
        
        $driver = DB::getDriverName();
        $tables = $this->getAllTables();
        
        if ($driver !== 'mysql') {
            $this->warn('Table optimization is only supported for MySQL databases.');
            return;
        }

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE `{$table}`");
                $bar->advance();
            } catch (\Exception $e) {
                $this->warn("Failed to optimize table {$table}: " . $e->getMessage());
                Log::warning("Failed to optimize table {$table}", ['error' => $e->getMessage()]);
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Table optimization completed.');
    }

    /**
     * Repair corrupted tables
     */
    protected function repairTables()
    {
        $this->info('Checking and repairing tables...');
        
        $driver = DB::getDriverName();
        
        if ($driver !== 'mysql') {
            $this->warn('Table repair is only supported for MySQL databases.');
            return;
        }

        $tables = $this->getAllTables();
        $repairedTables = 0;

        foreach ($tables as $table) {
            try {
                $result = DB::select("CHECK TABLE `{$table}`");
                
                if (isset($result[0]) && $result[0]->Msg_text !== 'OK') {
                    $this->warn("Table {$table} needs repair: " . $result[0]->Msg_text);
                    
                    DB::statement("REPAIR TABLE `{$table}`");
                    $this->info("Repaired table: {$table}");
                    $repairedTables++;
                    
                    Log::info("Repaired database table", ['table' => $table]);
                }
            } catch (\Exception $e) {
                $this->error("Failed to check/repair table {$table}: " . $e->getMessage());
                Log::error("Failed to check/repair table {$table}", ['error' => $e->getMessage()]);
            }
        }

        if ($repairedTables === 0) {
            $this->info('All tables are in good condition.');
        } else {
            $this->info("Repaired {$repairedTables} tables.");
        }
    }

    /**
     * Check for missing indexes on frequently queried columns
     */
    protected function checkIndexes()
    {
        $this->info('Checking for missing indexes...');
        
        $recommendations = [];
        
        // Check common patterns that should have indexes
        $indexChecks = [
            'students' => ['class_id', 'status', 'admission_number', 'academic_year'],
            'attendances' => ['student_id', 'date', 'class_id', 'status'],
            'fees' => ['student_id', 'payment_status', 'due_date', 'academic_year'],
            'results' => ['student_id', 'exam_id', 'subject_id'],
            'exams' => ['class_id', 'exam_date', 'status', 'academic_year'],
            'assignments' => ['class_id', 'subject_id', 'due_date', 'teacher_id'],
            'notifications' => ['recipient_id', 'is_read', 'notification_type'],
            'teachers' => ['user_id', 'status', 'is_active'],
            'users' => ['role', 'email', 'is_active']
        ];

        foreach ($indexChecks as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    if (!$this->hasIndexOnColumn($table, $column)) {
                        $recommendations[] = "Consider adding index on {$table}.{$column}";
                    }
                }
            }
        }

        if (empty($recommendations)) {
            $this->info('All recommended indexes are present.');
        } else {
            $this->warn('Index recommendations:');
            foreach ($recommendations as $recommendation) {
                $this->line("  - {$recommendation}");
            }
        }
    }

    /**
     * Vacuum database (SQLite only)
     */
    protected function vacuumDatabase()
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->warn('VACUUM is only supported for SQLite databases.');
            return;
        }

        $this->info('Running VACUUM on SQLite database...');
        
        try {
            DB::statement('VACUUM');
            $this->info('Database vacuum completed.');
            Log::info('SQLite database vacuum completed');
        } catch (\Exception $e) {
            $this->error('Failed to vacuum database: ' . $e->getMessage());
            Log::error('Failed to vacuum database', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get all table names
     */
    protected function getAllTables(): array
    {
        $driver = DB::getDriverName();
        
        try {
            switch ($driver) {
                case 'mysql':
                    $tables = DB::select('SHOW TABLES');
                    $key = 'Tables_in_' . DB::getDatabaseName();
                    return array_map(function($table) use ($key) {
                        return $table->$key;
                    }, $tables);
                    
                case 'pgsql':
                    $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                    return array_map(function($table) {
                        return $table->tablename;
                    }, $tables);
                    
                case 'sqlite':
                    $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                    return array_map(function($table) {
                        return $table->name;
                    }, $tables);
                    
                default:
                    return [];
            }
        } catch (\Exception $e) {
            $this->error('Failed to get table list: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a column has an index
     */
    protected function hasIndexOnColumn(string $table, string $column): bool
    {
        $driver = DB::getDriverName();
        
        try {
            switch ($driver) {
                case 'mysql':
                    $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Column_name = ?", [$column]);
                    return !empty($indexes);
                    
                case 'pgsql':
                    $indexes = DB::select("
                        SELECT indexname 
                        FROM pg_indexes 
                        WHERE tablename = ? 
                        AND indexdef LIKE ?
                    ", [$table, "%{$column}%"]);
                    return !empty($indexes);
                    
                case 'sqlite':
                    $indexes = DB::select("
                        SELECT name 
                        FROM sqlite_master 
                        WHERE type = 'index' 
                        AND tbl_name = ? 
                        AND sql LIKE ?
                    ", [$table, "%{$column}%"]);
                    return !empty($indexes);
                    
                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get database size information
     */
    protected function getDatabaseSize(): array
    {
        $driver = DB::getDriverName();
        
        try {
            switch ($driver) {
                case 'mysql':
                    $result = DB::select("
                        SELECT 
                            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                        FROM information_schema.tables 
                        WHERE table_schema = ?
                    ", [DB::getDatabaseName()]);
                    
                    return [
                        'size_mb' => $result[0]->size_mb ?? 0,
                        'driver' => 'mysql'
                    ];
                    
                case 'sqlite':
                    $dbPath = database_path('database.sqlite');
                    if (file_exists($dbPath)) {
                        $sizeBytes = filesize($dbPath);
                        return [
                            'size_mb' => round($sizeBytes / 1024 / 1024, 2),
                            'driver' => 'sqlite'
                        ];
                    }
                    break;
                    
                default:
                    return ['size_mb' => 0, 'driver' => $driver];
            }
        } catch (\Exception $e) {
            return ['size_mb' => 0, 'driver' => $driver, 'error' => $e->getMessage()];
        }
        
        return ['size_mb' => 0, 'driver' => $driver];
    }
}