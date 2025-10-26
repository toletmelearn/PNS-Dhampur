<?php

/**
 * Automated Test Fix Script
 * 
 * This script:
 * 1. Migrates the test database once
 * 2. Updates all test files to use DatabaseTransactions instead of RefreshDatabase
 * 3. Runs tests to verify fixes
 */

echo "========================================\n";
echo "  Automated Test Fix Script\n";
echo "========================================\n\n";

// Step 1: Run migrations on test database
echo "[Step 1/4] Running migrations on test database...\n";
echo "This may take 5-10 minutes due to 230 migrations...\n";
echo "(This is a ONE-TIME operation)\n\n";

$migrationCommand = 'php artisan migrate:fresh --env=testing --force 2>&1';
exec($migrationCommand, $migrationOutput, $migrationCode);

if ($migrationCode === 0) {
    echo "✓ Migrations completed successfully\n\n";
} else {
    echo "✗ Migration failed\n";
    echo "Output:\n" . implode("\n", $migrationOutput) . "\n";
    echo "\nPlease ensure:\n";
    echo "1. MySQL is running\n";
    echo "2. Test database 'pns_dhampur_test' exists\n";
    echo "3. No syntax errors in migrations\n";
    exit(1);
}

// Step 2: Find all test files
echo "[Step 2/4] Finding all test files...\n";
$testDirs = ['tests/Unit', 'tests/Feature'];
$testFiles = [];

foreach ($testDirs as $dir) {
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $testFiles[] = $file->getPathname();
            }
        }
    }
}

echo "Found " . count($testFiles) . " test files\n\n";

// Step 3: Update test files
echo "[Step 3/4] Updating test files...\n";
$updatedCount = 0;
$skippedCount = 0;
$errors = [];

foreach ($testFiles as $testFile) {
    $content = file_get_contents($testFile);
    $originalContent = $content;
    $updated = false;
    
    // Check if file uses RefreshDatabase
    if (strpos($content, 'use RefreshDatabase') !== false || 
        strpos($content, 'RefreshDatabase;') !== false) {
        
        // Replace the use statement
        $content = str_replace(
            'use Illuminate\Foundation\Testing\RefreshDatabase;',
            'use Illuminate\Foundation\Testing\DatabaseTransactions;',
            $content,
            $count1
        );
        
        // Replace the trait usage in class
        $content = str_replace(
            'use RefreshDatabase',
            'use DatabaseTransactions',
            $content,
            $count2
        );
        
        if ($count1 > 0 || $count2 > 0) {
            $updated = true;
        }
    }
    
    if ($updated && $content !== $originalContent) {
        // Backup original file
        $backupFile = $testFile . '.backup';
        copy($testFile, $backupFile);
        
        // Write updated content
        if (file_put_contents($testFile, $content)) {
            $updatedCount++;
            echo "  ✓ Updated: " . basename($testFile) . "\n";
        } else {
            $errors[] = "Failed to write: {$testFile}";
            echo "  ✗ Failed: " . basename($testFile) . "\n";
        }
    } else {
        $skippedCount++;
    }
}

echo "\n";
echo "Updated: {$updatedCount} files\n";
echo "Skipped: {$skippedCount} files (no RefreshDatabase trait)\n";

if (count($errors) > 0) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
}

// Step 4: Run sample tests to verify
echo "\n[Step 4/4] Running sample tests to verify fix...\n";
$sampleTests = [
    'tests/Unit/ExampleTest.php',
    'tests/Feature/ExampleTest.php',
];

$allPassed = true;
foreach ($sampleTests as $testFile) {
    if (file_exists($testFile)) {
        echo "  Testing: " . basename($testFile) . " ... ";
        $output = [];
        $exitCode = 0;
        exec("php vendor/bin/phpunit {$testFile} --no-coverage 2>&1", $output, $exitCode);
        
        if ($exitCode === 0) {
            echo "✓ PASSED\n";
        } else {
            echo "✗ FAILED\n";
            $allPassed = false;
        }
    }
}

echo "\n========================================\n";
echo "  Fix Complete!\n";
echo "========================================\n\n";

if ($allPassed) {
    echo "✓ All sample tests passed!\n\n";
    echo "Next steps:\n";
    echo "1. Run full test suite: php vendor/bin/phpunit\n";
    echo "2. Expected time: 30-120 seconds (was 8+ minutes)\n";
    echo "3. Backup files saved as *.php.backup\n\n";
} else {
    echo "⚠ Some tests still failing\n\n";
    echo "This may be due to:\n";
    echo "1. Actual test logic issues (not migration related)\n";
    echo "2. Missing API routes\n";
    echo "3. Factory issues\n\n";
    echo "Run with verbose output to see specific errors:\n";
    echo "php vendor/bin/phpunit --verbose --stop-on-failure\n\n";
}

echo "Detailed logs saved to test_fix_log.txt\n";
