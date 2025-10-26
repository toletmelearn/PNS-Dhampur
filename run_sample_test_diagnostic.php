<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

$testFiles = [
    'tests/Unit/ExampleTest.php' => 'Unit Example',
    'tests/Feature/ExampleTest.php' => 'Feature Example',
    'tests/Feature/BasicSecurityTest.php' => 'Basic Security',
];

echo "Running Sample Tests to Identify Failures\n";
echo "==========================================\n\n";

$results = [];

foreach ($testFiles as $file => $name) {
    echo "Testing: {$name}\n";
    echo str_repeat('-', 50) . "\n";
    
    $process = new Process(['php', 'vendor/bin/phpunit', $file, '--verbose', '--stop-on-failure']);
    $process->setTimeout(120);
    
    try {
        $process->run();
        
        $output = $process->getOutput() . $process->getErrorOutput();
        $exitCode = $process->getExitCode();
        
        $results[$name] = [
            'success' => $exitCode === 0,
            'exitCode' => $exitCode,
            'output' => $output
        ];
        
        if ($exitCode === 0) {
            echo "✓ PASSED\n";
        } else {
            echo "✗ FAILED (Exit Code: {$exitCode})\n";
            // Extract error messages
            preg_match_all('/Error:|Exception:|Failed asserting/i', $output, $matches);
            if (count($matches[0]) > 0) {
                echo "  Errors found: " . count($matches[0]) . "\n";
            }
        }
        
        echo "\n";
        
    } catch (ProcessFailedException $e) {
        echo "✗ PROCESS FAILED: " . $e->getMessage() . "\n\n";
        $results[$name] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

echo "\n";
echo "SUMMARY\n";
echo "==========================================\n";

foreach ($results as $name => $result) {
    $status = $result['success'] ? '✓ PASS' : '✗ FAIL';
    echo "{$status} - {$name}\n";
    
    if (!$result['success'] && isset($result['output'])) {
        // Try to extract meaningful error
        $lines = explode("\n", $result['output']);
        $errorLines = array_filter($lines, function($line) {
            return stripos($line, 'error') !== false || 
                   stripos($line, 'exception') !== false ||
                   stripos($line, 'failed') !== false;
        });
        
        if (count($errorLines) > 0) {
            echo "  First error: " . trim(array_values($errorLines)[0]) . "\n";
        }
    }
}

echo "\n";
echo "Detailed output saved to test_results_detailed.txt\n";
file_put_contents('test_results_detailed.txt', print_r($results, true));
