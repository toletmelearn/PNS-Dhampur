<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set environment to testing to use SQLite
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

// Refresh config
$app->make('config')->set('database.default', 'sqlite');
$app->make('config')->set('database.connections.sqlite.database', ':memory:');

// Reconnect to database
DB::purge();
DB::reconnect();

echo "DB Driver: " . DB::getDriverName() . PHP_EOL;

if (DB::getDriverName() === 'sqlite') {
    echo "Testing SQLite PRAGMA..." . PHP_EOL;
    
    // First create a test table
    try {
        DB::statement('CREATE TABLE IF NOT EXISTS test_table (id INTEGER PRIMARY KEY, name TEXT)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_test_name ON test_table(name)');
        
        $result = DB::select('PRAGMA index_list(test_table)');
        echo "PRAGMA result for test_table:" . PHP_EOL;
        var_dump($result);
        
        // Test the indexExists function logic
        $indexExists = function ($table, $indexName) {
            if (DB::getDriverName() === 'sqlite') {
                try {
                    $indexes = DB::select("PRAGMA index_list({$table})");
                    foreach ($indexes as $index) {
                        if ($index->name === $indexName) {
                            return true;
                        }
                    }
                    return false;
                } catch (\Exception $e) {
                    return false;
                }
            }
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        };
        
        echo "Index exists check for 'idx_test_name': " . ($indexExists('test_table', 'idx_test_name') ? 'true' : 'false') . PHP_EOL;
        echo "Index exists check for 'nonexistent_index': " . ($indexExists('test_table', 'nonexistent_index') ? 'true' : 'false') . PHP_EOL;
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . PHP_EOL;
    }
} else {
    echo "Not using SQLite, using: " . DB::getDriverName() . PHP_EOL;
}