<?php

/**
 * Quick Test File Updater
 * Replaces RefreshDatabase with DatabaseTransactions in all test files
 */

echo "Updating Test Files\n";
echo "===================\n\n";

// Find all test files
$testDirs = ['tests/Unit', 'tests/Feature'];
$testFiles = [];

foreach ($testDirs as $dir) {
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $testFiles[] = $file->getPathname();
            }
        }
    }
}

echo "Found " . count($testFiles) . " test files\n\n";

$updatedCount = 0;
$skippedCount = 0;

foreach ($testFiles as $testFile) {
    $content = file_get_contents($testFile);
    $originalContent = $content;
    $updated = false;
    
    // Check if file uses RefreshDatabase
    if (strpos($content, 'RefreshDatabase') !== false) {
        
        // Replace the import statement
        $content = str_replace(
            'use Illuminate\Foundation\Testing\RefreshDatabase;',
            '// RefreshDatabase removed - using DatabaseTransactions in base TestCase',
            $content
        );
        
        // Replace trait usage
        $content = preg_replace(
            '/use\s+RefreshDatabase\s*;/m',
            '// RefreshDatabase removed - using DatabaseTransactions in base TestCase',
            $content
        );
        
        // Also handle inline usage
        $content = preg_replace(
            '/,\s*RefreshDatabase/m',
            '',
            $content
        );
        
        $content = preg_replace(
            '/RefreshDatabase\s*,/m',
            '',
            $content
        );
        
        if ($content !== $originalContent) {
            $updated = true;
        }
    }
    
    if ($updated) {
        // Create backup
        if (!file_exists($testFile . '.backup')) {
            copy($testFile, $testFile . '.backup');
        }
        
        // Write updated content
        if (file_put_contents($testFile, $content)) {
            $updatedCount++;
            echo "✓ " . str_replace('tests/', '', $testFile) . "\n";
        } else {
            echo "✗ FAILED: " . basename($testFile) . "\n";
        }
    } else {
        $skippedCount++;
    }
}

echo "\n";
echo "===================\n";
echo "Summary:\n";
echo "  Updated: {$updatedCount} files\n";
echo "  Skipped: {$skippedCount} files\n";
echo "  Backups: Created\n";
echo "\n";
echo "Next steps:\n";
echo "1. Wait for migrations to complete (see other terminal)\n";
echo "2. Run: php vendor/bin/phpunit\n";
echo "3. Tests should now run in under 2 minutes\n";
