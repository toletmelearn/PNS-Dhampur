<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class PerformanceOptimizeCommand extends Command
{
    protected $signature = 'performance:optimize {--analyze : Only analyze without making changes}';
    protected $description = 'Optimize application performance by identifying and fixing bottlenecks';

    private $recommendations = [];
    private $optimizations = [];

    public function handle()
    {
        $this->info('Starting performance optimization analysis...');
        
        $this->analyzeDatabasePerformance();
        $this->analyzeCacheUsage();
        $this->analyzeQueryOptimization();
        $this->analyzeFileOptimization();
        $this->analyzeConfigOptimization();
        
        $this->displayRecommendations();
        
        if (!$this->option('analyze')) {
            $this->applyOptimizations();
        }
        
        return 0;
    }

    private function analyzeDatabasePerformance()
    {
        $this->info('Analyzing database performance...');
        
        try {
            // Check for missing indexes
            $this->checkMissingIndexes();
            
            // Check for slow queries
            $this->checkSlowQueries();
            
            // Check for N+1 query problems
            $this->checkNPlusOneQueries();
            
        } catch (\Exception $e) {
            $this->addRecommendation('Database', 'Cannot analyze database: ' . $e->getMessage(), 'high');
        }
    }

    private function analyzeCacheUsage()
    {
        $this->info('Analyzing cache usage...');
        
        // Check if cache is properly configured
        $cacheDriver = config('cache.default');
        if ($cacheDriver === 'file') {
            $this->addRecommendation('Cache', 'Consider using Redis or Memcached for better performance', 'medium');
        }
        
        // Check for cacheable queries
        $this->checkCacheableQueries();
        
        // Check cache hit ratio (if Redis)
        if ($cacheDriver === 'redis') {
            $this->checkCacheHitRatio();
        }
    }

    private function analyzeQueryOptimization()
    {
        $this->info('Analyzing query optimization...');
        
        // Check for inefficient queries in models
        $this->checkModelQueries();
        
        // Check for missing eager loading
        $this->checkEagerLoading();
    }

    private function analyzeFileOptimization()
    {
        $this->info('Analyzing file optimization...');
        
        // Check for large files
        $this->checkLargeFiles();
        
        // Check for unused files
        $this->checkUnusedFiles();
        
        // Check asset optimization
        $this->checkAssetOptimization();
    }

    private function analyzeConfigOptimization()
    {
        $this->info('Analyzing configuration optimization...');
        
        // Check if config is cached
        if (!File::exists(base_path('bootstrap/cache/config.php'))) {
            $this->addOptimization('Config', 'Cache configuration files', 'config:cache');
        }
        
        // Check if routes are cached
        if (!File::exists(base_path('bootstrap/cache/routes-v7.php'))) {
            $this->addOptimization('Routes', 'Cache route files', 'route:cache');
        }
        
        // Check if views are cached
        $viewCachePath = storage_path('framework/views');
        if (!File::exists($viewCachePath) || count(File::files($viewCachePath)) === 0) {
            $this->addOptimization('Views', 'Cache view files', 'view:cache');
        }
        
        // Check opcache configuration
        $this->checkOpcacheConfiguration();
    }

    private function checkMissingIndexes()
    {
        $tables = ['students', 'attendances', 'fees', 'users', 'exams', 'results'];
        
        foreach ($tables as $table) {
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table}");
                $indexedColumns = array_column($indexes, 'Column_name');
                
                // Common columns that should be indexed
                $shouldBeIndexed = ['created_at', 'updated_at', 'status', 'user_id', 'student_id'];
                
                foreach ($shouldBeIndexed as $column) {
                    if ($this->columnExists($table, $column) && !in_array($column, $indexedColumns)) {
                        $this->addRecommendation('Database', "Consider adding index on {$table}.{$column}", 'medium');
                    }
                }
            } catch (\Exception $e) {
                // Table might not exist, skip
            }
        }
    }

    private function checkSlowQueries()
    {
        try {
            // Enable slow query log temporarily for analysis
            DB::statement("SET GLOBAL slow_query_log = 'ON'");
            DB::statement("SET GLOBAL long_query_time = 1");
            
            $this->addRecommendation('Database', 'Slow query logging enabled for monitoring', 'info');
        } catch (\Exception $e) {
            $this->addRecommendation('Database', 'Cannot enable slow query logging: ' . $e->getMessage(), 'low');
        }
    }

    private function checkNPlusOneQueries()
    {
        $modelFiles = File::allFiles(app_path('Models'));
        
        foreach ($modelFiles as $file) {
            $content = File::get($file->getPathname());
            
            // Look for potential N+1 query patterns
            if (preg_match('/foreach.*->/', $content) && !preg_match('/with\(/', $content)) {
                $this->addRecommendation('N+1 Queries', "Potential N+1 query in {$file->getFilename()}", 'medium');
            }
        }
    }

    private function checkCacheableQueries()
    {
        $controllerFiles = File::allFiles(app_path('Http/Controllers'));
        
        foreach ($controllerFiles as $file) {
            $content = File::get($file->getPathname());
            
            // Look for queries that could be cached
            if (preg_match('/::all\(\)/', $content) && !preg_match('/Cache::/', $content)) {
                $this->addRecommendation('Cache', "Consider caching queries in {$file->getFilename()}", 'low');
            }
        }
    }

    private function checkCacheHitRatio()
    {
        try {
            $redis = Cache::getRedis();
            $info = $redis->info();
            
            if (isset($info['keyspace_hits']) && isset($info['keyspace_misses'])) {
                $hits = $info['keyspace_hits'];
                $misses = $info['keyspace_misses'];
                $total = $hits + $misses;
                
                if ($total > 0) {
                    $hitRatio = ($hits / $total) * 100;
                    
                    if ($hitRatio < 80) {
                        $this->addRecommendation('Cache', "Low cache hit ratio: {$hitRatio}%", 'medium');
                    } else {
                        $this->addRecommendation('Cache', "Good cache hit ratio: {$hitRatio}%", 'info');
                    }
                }
            }
        } catch (\Exception $e) {
            // Redis not available or configured
        }
    }

    private function checkModelQueries()
    {
        $modelFiles = File::allFiles(app_path('Models'));
        
        foreach ($modelFiles as $file) {
            $content = File::get($file->getPathname());
            
            // Check for select * queries
            if (preg_match('/select\s*\*/', $content)) {
                $this->addRecommendation('Query Optimization', "Avoid SELECT * in {$file->getFilename()}", 'low');
            }
            
            // Check for missing fillable/guarded
            if (!preg_match('/\$fillable/', $content) && !preg_match('/\$guarded/', $content)) {
                $this->addRecommendation('Security', "Missing fillable/guarded in {$file->getFilename()}", 'medium');
            }
        }
    }

    private function checkEagerLoading()
    {
        $controllerFiles = File::allFiles(app_path('Http/Controllers'));
        
        foreach ($controllerFiles as $file) {
            $content = File::get($file->getPathname());
            
            // Look for relationship access without eager loading
            if (preg_match('/\$\w+->(\w+)->/', $content) && !preg_match('/with\(/', $content)) {
                $this->addRecommendation('Eager Loading', "Consider eager loading in {$file->getFilename()}", 'medium');
            }
        }
    }

    private function checkLargeFiles()
    {
        $directories = [
            storage_path('app'),
            public_path('uploads'),
            public_path('assets'),
        ];
        
        foreach ($directories as $dir) {
            if (File::exists($dir)) {
                $files = File::allFiles($dir);
                
                foreach ($files as $file) {
                    $size = $file->getSize();
                    
                    if ($size > 10 * 1024 * 1024) { // 10MB
                        $this->addRecommendation('File Size', "Large file detected: {$file->getPathname()} (" . $this->formatBytes($size) . ")", 'low');
                    }
                }
            }
        }
    }

    private function checkUnusedFiles()
    {
        // Check for unused CSS/JS files
        $assetsPath = public_path('assets');
        
        if (!File::exists($assetsPath)) {
            return; // Skip if assets directory doesn't exist
        }
        
        $publicAssets = File::allFiles($assetsPath);
        $viewsPath = resource_path('views');
        
        if (!File::exists($viewsPath)) {
            return; // Skip if views directory doesn't exist
        }
        
        $viewFiles = File::allFiles($viewsPath);
        
        $usedAssets = [];
        
        foreach ($viewFiles as $view) {
            $content = File::get($view->getPathname());
            preg_match_all('/asset\(["\']([^"\']+)["\']\)/', $content, $matches);
            $usedAssets = array_merge($usedAssets, $matches[1]);
        }
        
        foreach ($publicAssets as $asset) {
            $relativePath = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $asset->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath); // Normalize path separators
            
            if (!in_array($relativePath, $usedAssets) && !in_array('assets/' . basename($relativePath), $usedAssets)) {
                $this->addRecommendation('Unused Files', "Potentially unused asset: {$relativePath}", 'low');
            }
        }
    }

    private function checkAssetOptimization()
    {
        // Check if assets are minified
        $assetsPath = public_path('assets');
        
        if (!File::exists($assetsPath)) {
            $this->addRecommendation('Asset Optimization', 'Assets directory does not exist', 'info');
            return;
        }
        
        $cssPath = $assetsPath . '/css';
        $jsPath = $assetsPath . '/js';
        
        if (File::exists($cssPath)) {
            $cssFiles = File::glob($cssPath . '/*.css');
            foreach ($cssFiles as $file) {
                if (!str_contains($file, '.min.')) {
                    $this->addRecommendation('Asset Optimization', "Consider minifying: " . basename($file), 'low');
                }
            }
        }
        
        if (File::exists($jsPath)) {
            $jsFiles = File::glob($jsPath . '/*.js');
            foreach ($jsFiles as $file) {
                if (!str_contains($file, '.min.')) {
                    $this->addRecommendation('Asset Optimization', "Consider minifying: " . basename($file), 'low');
                }
            }
        }
    }

    private function checkOpcacheConfiguration()
    {
        if (!extension_loaded('opcache')) {
            $this->addRecommendation('PHP', 'OPcache extension is not loaded', 'high');
            return;
        }
        
        $config = opcache_get_configuration();
        $status = opcache_get_status();
        
        if (!$config['directives']['opcache.enable']) {
            $this->addRecommendation('PHP', 'OPcache is not enabled', 'high');
        }
        
        if ($config['directives']['opcache.memory_consumption'] < 128) {
            $this->addRecommendation('PHP', 'OPcache memory consumption is low', 'medium');
        }
        
        if (isset($status['opcache_statistics'])) {
            $hitRate = $status['opcache_statistics']['opcache_hit_rate'];
            
            if ($hitRate < 90) {
                $this->addRecommendation('PHP', "OPcache hit rate is low: {$hitRate}%", 'medium');
            }
        }
    }

    private function columnExists($table, $column)
    {
        try {
            $columns = DB::select("SHOW COLUMNS FROM {$table}");
            return collect($columns)->pluck('Field')->contains($column);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function addRecommendation($category, $description, $priority)
    {
        $this->recommendations[] = [
            'category' => $category,
            'description' => $description,
            'priority' => $priority,
        ];
    }

    private function addOptimization($category, $description, $command)
    {
        $this->optimizations[] = [
            'category' => $category,
            'description' => $description,
            'command' => $command,
        ];
    }

    private function displayRecommendations()
    {
        $this->info("\n" . str_repeat('=', 60));
        $this->info('PERFORMANCE ANALYSIS RESULTS');
        $this->info(str_repeat('=', 60));
        
        if (empty($this->recommendations) && empty($this->optimizations)) {
            $this->info('✅ No performance issues found!');
            return;
        }
        
        if (!empty($this->recommendations)) {
            $this->info("\nRecommendations:");
            $this->info(str_repeat('-', 40));
            
            foreach ($this->recommendations as $rec) {
                $icon = match($rec['priority']) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🔵',
                    'info' => 'ℹ️',
                    default => '⚪'
                };
                
                $this->line("{$icon} [{$rec['category']}] {$rec['description']}");
            }
        }
        
        if (!empty($this->optimizations)) {
            $this->info("\nAvailable Optimizations:");
            $this->info(str_repeat('-', 40));
            
            foreach ($this->optimizations as $opt) {
                $this->line("🚀 [{$opt['category']}] {$opt['description']}");
            }
        }
    }

    private function applyOptimizations()
    {
        if (empty($this->optimizations)) {
            return;
        }
        
        $this->info("\nApplying optimizations...");
        
        foreach ($this->optimizations as $opt) {
            try {
                $this->info("Applying: {$opt['description']}");
                Artisan::call($opt['command']);
                $this->info("✅ {$opt['description']} completed");
            } catch (\Exception $e) {
                $this->error("❌ Failed to apply {$opt['description']}: " . $e->getMessage());
            }
        }
        
        $this->info("\nOptimizations completed!");
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}