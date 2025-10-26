@echo off
echo Running PHPUnit tests with error capture...
echo.

php vendor/bin/phpunit tests/Unit/ExampleTest.php --verbose > test_unit_example.log 2>&1
echo Unit Example Test completed. Exit code: %ERRORLEVEL%

echo.
echo Running one Feature test...
php vendor/bin/phpunit tests/Feature/ExampleTest.php --verbose > test_feature_example.log 2>&1
echo Feature Example Test completed. Exit code: %ERRORLEVEL%

echo.
echo Running BasicSecurityTest...
php vendor/bin/phpunit tests/Feature/BasicSecurityTest.php --filter test_login_page_is_accessible --verbose > test_basic_security.log 2>&1
echo BasicSecurityTest completed. Exit code: %ERRORLEVEL%

echo.
echo Test logs created. Check the .log files for details.
pause
