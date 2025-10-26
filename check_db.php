<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Check main database connection
    echo "Testing main database connection...\n";
    DB::connection()->getPdo();
    echo "Main database connected: " . DB::connection()->getDatabaseName() . "\n";
    
    // Check test database connection
    echo "\nTesting test database connection...\n";
    config(['database.default' => 'mysql']);
    config(['database.connections.mysql.database' => 'pns_dhampur_test']);
    
    try {
        DB::connection()->getPdo();
        echo "Test database connected: " . DB::connection()->getDatabaseName() . "\n";
    } catch (\Exception $e) {
        echo "Test database connection failed: " . $e->getMessage() . "\n";
        echo "Creating test database...\n";
        
        // Try to create the test database
        config(['database.connections.mysql.database' => 'pns_dhampur']);
        DB::statement("CREATE DATABASE IF NOT EXISTS pns_dhampur_test");
        echo "Test database created successfully\n";
    }
    
} catch (\Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
