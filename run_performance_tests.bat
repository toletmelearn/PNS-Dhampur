@echo off
echo PNS-Dhampur Performance Testing Suite
echo =====================================

REM Set default parameters
set THREADS=50
set DURATION=5
set TEST_TYPE=attendance
set MONITOR=true

REM Parse command line arguments
:parse_args
if "%~1"=="" goto :end_parse
if /i "%~1"=="--threads" set THREADS=%~2& shift & shift & goto :parse_args
if /i "%~1"=="--duration" set DURATION=%~2& shift & shift & goto :parse_args
if /i "%~1"=="--test" set TEST_TYPE=%~2& shift & shift & goto :parse_args
if /i "%~1"=="--no-monitor" set MONITOR=false& shift & goto :parse_args
shift
goto :parse_args
:end_parse

echo Configuration:
echo - Test type: %TEST_TYPE%
echo - Concurrent users: %THREADS%
echo - Test duration: %DURATION% minutes
echo - System monitoring: %MONITOR%
echo.

REM Generate test data if needed
echo Generating test data...
php generate_test_data.php
if %ERRORLEVEL% NEQ 0 (
    echo Error generating test data!
    exit /b %ERRORLEVEL%
)

REM Start monitoring if enabled
if "%MONITOR%"=="true" (
    echo Starting performance monitoring...
    start "Performance Monitor" cmd /c "php monitor_performance.php %DURATION% %TEST_TYPE%_test"
)

REM Run JMeter test
echo Running JMeter performance test...
set JMETER_PATH=C:\apache-jmeter\bin\jmeter.bat
if not exist "%JMETER_PATH%" (
    echo JMeter not found at %JMETER_PATH%
    echo Please install JMeter or update the path in this script
    exit /b 1
)

"%JMETER_PATH%" -n -t jmeter_%TEST_TYPE%_test.jmx ^
    -Jhost=localhost ^
    -Jport=80 ^
    -Jprotocol=http ^
    -JnumThreads=%THREADS% ^
    -JrampUp=30 ^
    -Jduration=%DURATION% ^
    -l results_%TEST_TYPE%.jtl ^
    -e -o report_%TEST_TYPE%

if %ERRORLEVEL% NEQ 0 (
    echo Error running JMeter test!
    exit /b %ERRORLEVEL%
)

REM Generate performance report
echo Generating performance report...
echo # Performance Test Results > performance_report_%TEST_TYPE%.md
echo ## Test Configuration >> performance_report_%TEST_TYPE%.md
echo - Test Type: %TEST_TYPE% >> performance_report_%TEST_TYPE%.md
echo - Duration: %DURATION% minutes >> performance_report_%TEST_TYPE%.md
echo - Concurrent Users: %THREADS% >> performance_report_%TEST_TYPE%.md
echo - Test Date: %DATE% %TIME% >> performance_report_%TEST_TYPE%.md
echo. >> performance_report_%TEST_TYPE%.md

echo ## Summary >> performance_report_%TEST_TYPE%.md
echo See detailed report in report_%TEST_TYPE% directory >> performance_report_%TEST_TYPE%.md
echo. >> performance_report_%TEST_TYPE%.md

echo ## Next Steps >> performance_report_%TEST_TYPE%.md
echo 1. Review the detailed report in the report_%TEST_TYPE% directory >> performance_report_%TEST_TYPE%.md
echo 2. Compare results with performance benchmarks >> performance_report_%TEST_TYPE%.md
echo 3. Analyze any performance bottlenecks >> performance_report_%TEST_TYPE%.md
echo 4. Implement optimizations as needed >> performance_report_%TEST_TYPE%.md

echo Performance test completed successfully!
echo Results saved to:
echo - JMeter results: results_%TEST_TYPE%.jtl
echo - Detailed report: report_%TEST_TYPE%\
echo - Summary report: performance_report_%TEST_TYPE%.md
echo - Performance metrics: performance_metrics.csv (if monitoring was enabled)

exit /b 0