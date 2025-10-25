#!/bin/bash

echo "PNS-Dhampur Performance Testing Suite"
echo "====================================="

# Set default parameters
THREADS=50
DURATION=5
TEST_TYPE="attendance"
MONITOR=true

# Parse command line arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --threads)
      THREADS="$2"
      shift 2
      ;;
    --duration)
      DURATION="$2"
      shift 2
      ;;
    --test)
      TEST_TYPE="$2"
      shift 2
      ;;
    --no-monitor)
      MONITOR=false
      shift
      ;;
    *)
      echo "Unknown option: $1"
      exit 1
      ;;
  esac
done

echo "Configuration:"
echo "- Test type: $TEST_TYPE"
echo "- Concurrent users: $THREADS"
echo "- Test duration: $DURATION minutes"
echo "- System monitoring: $MONITOR"
echo ""

# Generate test data if needed
echo "Generating test data..."
php generate_test_data.php
if [ $? -ne 0 ]; then
    echo "Error generating test data!"
    exit 1
fi

# Start monitoring if enabled
if [ "$MONITOR" = true ]; then
    echo "Starting performance monitoring..."
    php monitor_performance.php $(($DURATION * 60)) "${TEST_TYPE}_test" &
    MONITOR_PID=$!
fi

# Run JMeter test
echo "Running JMeter performance test..."
JMETER_PATH="jmeter"
if ! command -v $JMETER_PATH &> /dev/null; then
    echo "JMeter not found in PATH"
    echo "Please install JMeter or update the PATH environment variable"
    exit 1
fi

$JMETER_PATH -n -t "jmeter_${TEST_TYPE}_test.jmx" \
    -Jhost=localhost \
    -Jport=80 \
    -Jprotocol=http \
    -JnumThreads=$THREADS \
    -JrampUp=30 \
    -Jduration=$DURATION \
    -l "results_${TEST_TYPE}.jtl" \
    -e -o "report_${TEST_TYPE}"

if [ $? -ne 0 ]; then
    echo "Error running JMeter test!"
    exit 1
fi

# Wait for monitoring to complete if it was started
if [ "$MONITOR" = true ]; then
    wait $MONITOR_PID
fi

# Generate performance report
echo "Generating performance report..."
cat > "performance_report_${TEST_TYPE}.md" << EOF
# Performance Test Results

## Test Configuration
- Test Type: $TEST_TYPE
- Duration: $DURATION minutes
- Concurrent Users: $THREADS
- Test Date: $(date)

## Summary
See detailed report in report_${TEST_TYPE} directory

## Next Steps
1. Review the detailed report in the report_${TEST_TYPE} directory
2. Compare results with performance benchmarks
3. Analyze any performance bottlenecks
4. Implement optimizations as needed
EOF

echo "Performance test completed successfully!"
echo "Results saved to:"
echo "- JMeter results: results_${TEST_TYPE}.jtl"
echo "- Detailed report: report_${TEST_TYPE}/"
echo "- Summary report: performance_report_${TEST_TYPE}.md"
echo "- Performance metrics: performance_metrics.csv (if monitoring was enabled)"

exit 0