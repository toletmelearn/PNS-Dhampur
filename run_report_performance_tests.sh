#!/bin/bash

# Report Generation Performance Test Execution Script for Linux/Unix
# This script automates the execution of performance tests for report generation

echo "==================================================="
echo "PNS-Dhampur Report Generation Performance Test Tool"
echo "==================================================="

# Default parameters
THREADS=50
DURATION=300
TEST_TYPE="report"
MONITORING=true
JMETER_PATH="/opt/apache-jmeter/bin"
PHP_PATH="php"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -t|--threads)
            THREADS="$2"
            shift 2
            ;;
        -d|--duration)
            DURATION="$2"
            shift 2
            ;;
        -type|--test-type)
            TEST_TYPE="$2"
            shift 2
            ;;
        -m|--monitoring)
            MONITORING="$2"
            shift 2
            ;;
        -jmeter|--jmeter-path)
            JMETER_PATH="$2"
            shift 2
            ;;
        -php|--php-path)
            PHP_PATH="$2"
            shift 2
            ;;
        -h|--help)
            echo ""
            echo "Usage: ./run_report_performance_tests.sh [options]"
            echo ""
            echo "Options:"
            echo "  -t, --threads NUM       Number of concurrent threads (default: 50)"
            echo "  -d, --duration NUM      Test duration in seconds (default: 300)"
            echo "  -type, --test-type TYPE Test type: report, attendance, academic, financial, exam (default: report)"
            echo "  -m, --monitoring BOOL   Enable monitoring (true/false, default: true)"
            echo "  -jmeter, --jmeter-path PATH  Path to JMeter bin directory (default: /opt/apache-jmeter/bin)"
            echo "  -php, --php-path PATH   Path to PHP executable (default: php)"
            echo "  -h, --help              Show this help message"
            echo ""
            echo "Example: ./run_report_performance_tests.sh -t 100 -d 600 -type financial"
            echo ""
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

echo "Configuration:"
echo "- Threads: $THREADS"
echo "- Duration: $DURATION seconds"
echo "- Test Type: $TEST_TYPE"
echo "- Monitoring: $MONITORING"
echo "- JMeter Path: $JMETER_PATH"
echo "- PHP Path: $PHP_PATH"
echo ""

# Create output directories
mkdir -p performance_results/reports

# Step 1: Generate test data
echo "[1/4] Generating test data..."
$PHP_PATH generate_report_test_data.php
if [ $? -ne 0 ]; then
    echo "Error generating test data!"
    exit 1
fi
echo "Test data generated successfully."
echo ""

# Step 2: Start performance monitoring if enabled
if [ "$MONITORING" = "true" ]; then
    echo "[2/4] Starting performance monitoring..."
    $PHP_PATH monitor_report_performance.php &
    MONITOR_PID=$!
    echo "Performance monitoring started (PID: $MONITOR_PID)."
else
    echo "[2/4] Performance monitoring disabled."
fi
echo ""

# Step 3: Run JMeter test
echo "[3/4] Running JMeter test with $THREADS threads for $DURATION seconds..."

# Determine which JMeter test file to use based on test type
JMX_FILE="jmeter_report_generation_test.jmx"

# Set JMeter properties
JMETER_PROPS="-Jthreads=$THREADS -Jduration=$DURATION -Jtest.type=$TEST_TYPE"

# Run JMeter test
"$JMETER_PATH/jmeter" -n -t $JMX_FILE $JMETER_PROPS -l performance_results/results.jtl -j performance_results/jmeter.log

if [ $? -ne 0 ]; then
    echo "Error running JMeter test!"
    exit 1
fi
echo "JMeter test completed successfully."
echo ""

# Step 4: Generate HTML report
echo "[4/4] Generating performance report..."
"$JMETER_PATH/jmeter" -g performance_results/results.jtl -o performance_results/reports/html_report

if [ $? -ne 0 ]; then
    echo "Error generating HTML report!"
    exit 1
fi
echo "Performance report generated successfully."
echo ""

# Stop monitoring if it was started
if [ "$MONITORING" = "true" ] && [ -n "$MONITOR_PID" ]; then
    kill $MONITOR_PID 2>/dev/null
    echo "Performance monitoring stopped."
fi

# Display results summary
echo "==================================================="
echo "Performance Test Results Summary"
echo "==================================================="
echo "Test completed with $THREADS concurrent users for $DURATION seconds"
echo "Results saved to:"
echo "- Raw results: performance_results/results.jtl"
echo "- HTML report: performance_results/reports/html_report"
echo "- JMeter log: performance_results/jmeter.log"
echo ""
echo "To view the HTML report, open:"
echo "performance_results/reports/html_report/index.html"
echo "==================================================="

exit 0