<?php

echo "Restoring original routes...\n";

// Restore original routes file
if (file_exists('routes/api.php.backup2')) {
    copy('routes/api.php.backup2', 'routes/api.php');
    echo "✅ Restored original routes/api.php\n";
    
    // Clean up backup and test files
    unlink('routes/api.php.backup2');
    if (file_exists('routes/api_test.php')) {
        unlink('routes/api_test.php');
    }
    echo "✅ Cleaned up temporary files\n";
} else {
    echo "❌ Backup file not found\n";
}

echo "Routes restoration completed.\n";