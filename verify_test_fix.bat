@echo off
echo ====================================
echo  Test Suite Verification Script
echo ====================================
echo.

echo [1/3] Checking migration status...
php artisan migrate:status --env=testing | findstr /C:"Pending"
if errorlevel 1 (
    echo   OK: All migrations completed
) else (
    echo   WARNING: Some migrations still pending
    echo   Run: php artisan migrate --env=testing --force
    pause
    exit /b 1
)

echo.
echo [2/3] Running sample tests...
php vendor/bin/phpunit tests/Unit/ExampleTest.php --no-coverage
if errorlevel 1 (
    echo   FAILED: Sample test failed
    pause
    exit /b 1
) else (
    echo   OK: Sample test passed
)

echo.
echo [3/3] Running full test suite...
echo This should complete in under 2 minutes...
echo.

php vendor/bin/phpunit --no-coverage

echo.
echo ====================================
echo  Verification Complete
echo ====================================
echo.
echo Check the output above:
echo - If most tests PASS: SUCCESS!
echo - If tests run FAST (under 2 min): Performance fix WORKED!
echo - If tests still slow: Check TEST_FIX_SUMMARY.md
echo.
pause
