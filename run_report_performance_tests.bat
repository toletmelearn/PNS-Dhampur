@echo off
setlocal enabledelayedexpansion

REM Report Generation Performance Test Execution Script for Windows
REM This script automates the execution of performance tests for report generation

echo ===================================================
echo PNS-Dhampur Report Generation Performance Test Tool
echo ===================================================

REM Default parameters
set THREADS=50
set DURATION=300
set TEST_TYPE=report
set MONITORING=true
set JMETER_PATH=C:\apache-jmeter\bin
set PHP_PATH=C:\xampp\php\php.exe

REM Parse command line arguments
:parse_args
if "%~1"=="" goto :end_parse_args
if /i "%~1"=="-t" set THREADS=%~2& shift & shift & goto :parse_args
if /i "%~1"=="-d" set DURATION=%~2& shift & shift & goto :parse_args
if /i "%~1"=="-type" set TEST_TYPE=%~2& shift & shift & goto :parse_args
if /i "%~1"=="-m" set MONITORING=%~2& shift & shift & goto :parse_args
if /i "%~1"=="-jmeter" set JMETER_PATH=%~2& shift & shift & goto :parse_args
if /i "%~1"=="-php" set PHP_PATH=%~2& shift & shift & goto :parse_args
if /i "%~1"=="-h" goto :show_help
if /i "%~1"=="--help" goto :show_help
shift
goto :parse_args

:show_help
echo.
echo Usage: run_report_performance_tests.bat [options]
echo.
echo Options:
echo   -t NUM        Number of concurrent threads (default: 50)
echo   -d NUM        Test duration in seconds (default: 300)
echo   -type TYPE    Test type: report, attendance, academic, financial, exam (default: report)
echo   -m BOOL       Enable monitoring (true/false, default: true)
echo   -jmeter PATH  Path to JMeter bin directory (default: C:\apache-jmeter\bin)
echo   -php PATH     Path to PHP executable (default: C:\xampp\php\php.exe)
echo   -h, --help    Show this help message
echo.
echo Example: run_report_performance_tests.bat -t 100 -d 600 -type financial
echo.
exit /b 0

:end_parse_args

echo Configuration:
echo - Threads: %THREADS%
echo - Duration: %DURATION% seconds
echo - Test Type: %TEST_TYPE%
echo - Monitoring: %MONITORING%
echo - JMeter Path: %JMETER_PATH%
echo - PHP Path: %PHP_PATH%
echo.

REM Create output directories
if not exist "performance_results" mkdir performance_results
if not exist "performance_results\reports" mkdir performance_results\reports

REM Step 1: Generate test data
echo [1/4] Generating test data...
%PHP_PATH% generate_report_test_data.php
if %ERRORLEVEL% neq 0 (
    echo Error generating test data!
    exit /b 1
)
echo Test data generated successfully.
echo.

REM Step 2: Start performance monitoring if enabled
if /i "%MONITORING%"=="true" (
    echo [2/4] Starting performance monitoring...
    start "Performance Monitoring" %PHP_PATH% monitor_report_performance.php
    echo Performance monitoring started.
) else (
    echo [2/4] Performance monitoring disabled.
)
echo.

REM Step 3: Run JMeter test
echo [3/4] Running JMeter test with %THREADS% threads for %DURATION% seconds...

REM Determine which JMeter test file to use based on test type
set JMX_FILE=jmeter_report_generation_test.jmx

REM Set JMeter properties
set JMETER_PROPS=-Jthreads=%THREADS% -Jduration=%DURATION% -Jtest.type=%TEST_TYPE%

REM Run JMeter test
"%JMETER_PATH%\jmeter.bat" -n -t %JMX_FILE% %JMETER_PROPS% -l performance_results\results.jtl -j performance_results\jmeter.log

if %ERRORLEVEL% neq 0 (
    echo Error running JMeter test!
    exit /b 1
)
echo JMeter test completed successfully.
echo.

REM Step 4: Generate HTML report
echo [4/4] Generating performance report...
"%JMETER_PATH%\jmeter.bat" -g performance_results\results.jtl -o performance_results\reports\html_report

if %ERRORLEVEL% neq 0 (
    echo Error generating HTML report!
    exit /b 1
)
echo Performance report generated successfully.
echo.

REM Display results summary
echo ===================================================
echo Performance Test Results Summary
echo ===================================================
echo Test completed with %THREADS% concurrent users for %DURATION% seconds
echo Results saved to:
echo - Raw results: performance_results\results.jtl
echo - HTML report: performance_results\reports\html_report
echo - JMeter log: performance_results\jmeter.log
echo.
echo To view the HTML report, open:
echo performance_results\reports\html_report\index.html
echo ===================================================

endlocal
exit /b 0