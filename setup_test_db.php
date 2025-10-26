<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

try {
    echo "Setting up test environment...\n\n";
    
    // Step 1: Create test database if it doesn't exist
    echo "Step 1: Creating test database...\n";
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `pns_dhampur_test`");
        echo "✓ Test database 'pns_dhampur_test' ready\n\n";
    } catch (PDOException $e) {
        echo "✗ Failed to create test database: " . $e->getMessage() . "\n";
        echo "Make sure MySQL is running (XAMPP)\n";
        exit(1);
    }
    
    // Step 2: Set environment to testing
    echo "Step 2: Setting test environment...\n";
    putenv('APP_ENV=testing');
    putenv('DB_DATABASE=pns_dhampur_test');
    config(['app.env' => 'testing']);
    config(['database.connections.mysql.database' => 'pns_dhampur_test']);
    echo "✓ Environment set to testing\n\n";
    
    // Step 3: Run migrations
    echo "Step 3: Running migrations on test database...\n";
    try {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Artisan::call('migrate:fresh', ['--database' => 'mysql', '--force' => true]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        echo "✓ Migrations completed\n\n";
    } catch (\Exception $e) {
        echo "✗ Migration failed: " . $e->getMessage() . "\n";
        echo "Full error:\n";
        echo $e->getTraceAsString() . "\n";
    }
    
    // Step 4: Check tables
    echo "Step 4: Checking created tables...\n";
    $tables = DB::select('SHOW TABLES');
    $tableCount = count($tables);
    echo "✓ Found {$tableCount} tables in test database\n";
    
    if ($tableCount > 0) {
        echo "First 10 tables:\n";
        foreach (array_slice($tables, 0, 10) as $table) {
            $tableName = array_values((array)$table)[0];
            echo "  - {$tableName}\n";
        }
    }
    
    echo "\n✓ Test database setup completed successfully!\n";
    echo "\nYou can now run: php vendor/bin/phpunit\n";
    
} catch (\Exception $e) {
    echo "✗ Setup failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
