<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Database Table Structure Check ===\n\n";

try {
    // Check schools table
    echo "1. Checking 'schools' table:\n";
    if (Schema::hasTable('schools')) {
        echo "   ✓ 'schools' table exists\n";
        $columns = Schema::getColumnListing('schools');
        echo "   Columns: " . implode(', ', $columns) . "\n";
    } else {
        echo "   ✗ 'schools' table does not exist\n";
    }
    
    echo "\n2. Checking 'users' table:\n";
    if (Schema::hasTable('users')) {
        echo "   ✓ 'users' table exists\n";
        $columns = Schema::getColumnListing('users');
        echo "   Columns: " . implode(', ', $columns) . "\n";
    } else {
        echo "   ✗ 'users' table does not exist\n";
    }
    
    echo "\n3. Checking 'roles' table:\n";
    if (Schema::hasTable('roles')) {
        echo "   ✓ 'roles' table exists\n";
        $columns = Schema::getColumnListing('roles');
        echo "   Columns: " . implode(', ', $columns) . "\n";
    } else {
        echo "   ✗ 'roles' table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}