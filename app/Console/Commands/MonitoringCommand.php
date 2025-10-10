<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MonitoringService;
use Illuminate\Support\Facades\Log;

class MonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:check 
                            {--format=table : Output format (table, json, text)}
                            {--alert : Send alerts for critical issues}
                            {--detailed : Show detailed information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive system monitoring and health checks';

    protected MonitoringService $monitoringService;

    /**
     * Create a new command instance.
     */
    public function __construct(MonitoringService $monitoringService)
    {
        parent::__construct();
        $this->monitoringService = $monitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting system monitoring check...');

        try {
            $results = $this->monitoringService->performHealthCheck();

            $this->displayResults($results);

            // Log the monitoring check
            Log::info('Monitoring check completed', [
                'status' => $results['overall_status'],
                'timestamp' => $results['timestamp'],
            ]);

            // Return appropriate exit code
            return $results['overall_status'] === 'critical' ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('Monitoring check failed: ' . $e->getMessage());
            Log::error('Monitoring check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Display monitoring results
     */
    protected function displayResults(array $results): void
    {
        $format = $this->option('format');

        switch ($format) {
            case 'json':
                $this->displayJsonResults($results);
                break;
            case 'text':
                $this->displayTextResults($results);
                break;
            default:
                $this->displayTableResults($results);
                break;
        }
    }

    /**
     * Display results in table format
     */
    protected function displayTableResults(array $results): void
    {
        // Overall status
        $statusColor = $this->getStatusColor($results['overall_status']);
        $this->line('');
        $this->line("<fg={$statusColor}>Overall Status: " . strtoupper($results['overall_status']) . '</fg>');
        $this->line('Timestamp: ' . $results['timestamp']);
        $this->line('');

        // Detailed checks
        $tableData = [];
        
        foreach ($results['checks'] as $category => $checks) {
            if (is_array($checks) && isset($checks['status'])) {
                // Single check
                $tableData[] = [
                    'Category' => ucfirst(str_replace('_', ' ', $category)),
                    'Status' => strtoupper($checks['status']),
                    'Message' => $checks['message'] ?? 'N/A',
                    'Details' => $this->formatDetails($checks),
                ];
            } elseif (is_array($checks)) {
                // Multiple checks in category
                foreach ($checks as $checkName => $check) {
                    if (isset($check['status'])) {
                        $tableData[] = [
                            'Category' => ucfirst(str_replace('_', ' ', $category)) . ' - ' . ucfirst(str_replace('_', ' ', $checkName)),
                            'Status' => strtoupper($check['status']),
                            'Message' => $check['message'] ?? 'N/A',
                            'Details' => $this->formatDetails($check),
                        ];
                    }
                }
            }
        }

        if (!empty($tableData)) {
            $this->table(['Category', 'Status', 'Message', 'Details'], $tableData);
        }

        // Summary
        $this->displaySummary($results);
    }

    /**
     * Display results in JSON format
     */
    protected function displayJsonResults(array $results): void
    {
        $this->line(json_encode($results, JSON_PRETTY_PRINT));
    }

    /**
     * Display results in text format
     */
    protected function displayTextResults(array $results): void
    {
        $this->line('System Monitoring Report');
        $this->line('========================');
        $this->line('');
        $this->line('Overall Status: ' . strtoupper($results['overall_status']));
        $this->line('Timestamp: ' . $results['timestamp']);
        $this->line('');

        foreach ($results['checks'] as $category => $checks) {
            $this->line(strtoupper(str_replace('_', ' ', $category)) . ':');
            
            if (is_array($checks) && isset($checks['status'])) {
                $this->line('  Status: ' . strtoupper($checks['status']));
                $this->line('  Message: ' . ($checks['message'] ?? 'N/A'));
                if ($this->option('detailed')) {
                    $this->displayDetailedInfo($checks);
                }
            } elseif (is_array($checks)) {
                foreach ($checks as $checkName => $check) {
                    if (isset($check['status'])) {
                        $this->line('  ' . ucfirst(str_replace('_', ' ', $checkName)) . ':');
                        $this->line('    Status: ' . strtoupper($check['status']));
                        $this->line('    Message: ' . ($check['message'] ?? 'N/A'));
                        if ($this->option('detailed')) {
                            $this->displayDetailedInfo($check, '    ');
                        }
                    }
                }
            }
            $this->line('');
        }
    }

    /**
     * Display summary information
     */
    protected function displaySummary(array $results): void
    {
        $statusCounts = [
            'healthy' => 0,
            'warning' => 0,
            'critical' => 0,
        ];

        foreach ($results['checks'] as $checks) {
            if (is_array($checks) && isset($checks['status'])) {
                $statusCounts[$checks['status']] = ($statusCounts[$checks['status']] ?? 0) + 1;
            } elseif (is_array($checks)) {
                foreach ($checks as $check) {
                    if (isset($check['status'])) {
                        $statusCounts[$check['status']] = ($statusCounts[$check['status']] ?? 0) + 1;
                    }
                }
            }
        }

        $this->line('');
        $this->line('Summary:');
        $this->line("  <fg=green>Healthy: {$statusCounts['healthy']}</fg>");
        $this->line("  <fg=yellow>Warning: {$statusCounts['warning']}</fg>");
        $this->line("  <fg=red>Critical: {$statusCounts['critical']}</fg>");
    }

    /**
     * Format details for display
     */
    protected function formatDetails(array $check): string
    {
        $details = [];
        
        foreach ($check as $key => $value) {
            if (!in_array($key, ['status', 'message']) && !is_array($value)) {
                $details[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
            }
        }

        return implode(', ', $details);
    }

    /**
     * Display detailed information
     */
    protected function displayDetailedInfo(array $check, string $indent = '  '): void
    {
        foreach ($check as $key => $value) {
            if (!in_array($key, ['status', 'message']) && !is_array($value)) {
                $this->line($indent . ucfirst(str_replace('_', ' ', $key)) . ': ' . $value);
            }
        }
    }

    /**
     * Get color for status
     */
    protected function getStatusColor(string $status): string
    {
        switch ($status) {
            case 'healthy':
                return 'green';
            case 'warning':
                return 'yellow';
            case 'critical':
                return 'red';
            default:
                return 'white';
        }
    }
}