<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\SecurityAuditService;
use Carbon\Carbon;

class SecurityAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:audit 
                            {--report : Generate detailed security report}
                            {--export= : Export report to file (json|html|txt)}
                            {--fix : Attempt to fix common security issues}
                            {--schedule : Run as scheduled audit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive security audit and generate security reports';

    protected SecurityAuditService $securityAuditService;

    /**
     * Create a new command instance.
     */
    public function __construct(SecurityAuditService $securityAuditService)
    {
        parent::__construct();
        $this->securityAuditService = $securityAuditService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting security audit...');
        $startTime = microtime(true);

        try {
            // Run security audit
            $results = $this->securityAuditService->runSecurityAudit();
            
            // Log results
            $this->securityAuditService->logAuditResults($results);

            // Display results
            $this->displayResults($results);

            // Generate report if requested
            if ($this->option('report') || $this->option('export')) {
                $this->generateReport($results);
            }

            // Attempt fixes if requested
            if ($this->option('fix')) {
                $this->attemptFixes($results);
            }

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            $this->info("Security audit completed in {$duration} seconds.");

            // Return appropriate exit code
            $highSeverityCount = $results['summary']['high_severity'];
            return $highSeverityCount > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error('Security audit failed: ' . $e->getMessage());
            Log::error('Security audit failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Display audit results
     */
    protected function displayResults(array $results): void
    {
        $summary = $results['summary'];
        
        $this->newLine();
        $this->info('=== SECURITY AUDIT RESULTS ===');
        $this->newLine();

        // Display security score
        $grade = $summary['security_grade'];
        $percentage = $summary['security_percentage'];
        
        $gradeColor = match($grade) {
            'A' => 'green',
            'B' => 'yellow',
            'C' => 'yellow',
            'D' => 'red',
            'F' => 'red',
            default => 'white'
        };

        $this->line("Security Grade: <fg={$gradeColor}>{$grade}</> ({$percentage}%)");
        $this->line("Total Checks: {$summary['total_checks']}");
        $this->newLine();

        // Display vulnerability summary
        $this->info('Vulnerability Summary:');
        $this->line("  High Severity: <fg=red>{$summary['high_severity']}</>");
        $this->line("  Medium Severity: <fg=yellow>{$summary['medium_severity']}</>");
        $this->line("  Low Severity: <fg=blue>{$summary['low_severity']}</>");
        $this->line("  Total: {$summary['vulnerabilities_found']}");
        $this->newLine();

        // Display vulnerabilities
        if (!empty($results['vulnerabilities'])) {
            $this->info('Vulnerabilities Found:');
            $this->newLine();

            foreach ($results['vulnerabilities'] as $index => $vulnerability) {
                $severityColor = match($vulnerability['severity']) {
                    'HIGH' => 'red',
                    'MEDIUM' => 'yellow',
                    'LOW' => 'blue',
                    default => 'white'
                };

                $this->line(($index + 1) . ". <fg={$severityColor}>[{$vulnerability['severity']}]</> {$vulnerability['type']}");
                $this->line("   Description: {$vulnerability['description']}");
                $this->line("   Recommendation: {$vulnerability['recommendation']}");
                
                // Display additional details if available
                if (isset($vulnerability['files']) && !empty($vulnerability['files'])) {
                    $fileCount = count($vulnerability['files']);
                    $displayFiles = array_slice($vulnerability['files'], 0, 3);
                    $this->line("   Affected files: " . implode(', ', $displayFiles) . 
                               ($fileCount > 3 ? " (and " . ($fileCount - 3) . " more)" : ""));
                }

                if (isset($vulnerability['issues']) && !empty($vulnerability['issues'])) {
                    $this->line("   Issues: " . implode(', ', $vulnerability['issues']));
                }

                $this->newLine();
            }
        } else {
            $this->info('No vulnerabilities found! 🎉');
        }

        // Display recommendations
        if (!empty($results['recommendations'])) {
            $this->info('Security Recommendations:');
            foreach ($results['recommendations'] as $index => $recommendation) {
                $this->line(($index + 1) . ". {$recommendation}");
            }
            $this->newLine();
        }
    }

    /**
     * Generate security report
     */
    protected function generateReport(array $results): void
    {
        $exportFormat = $this->option('export') ?: 'json';
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "security_audit_{$timestamp}";

        $reportsDir = storage_path('app/security-reports');
        if (!File::exists($reportsDir)) {
            File::makeDirectory($reportsDir, 0755, true);
        }

        switch (strtolower($exportFormat)) {
            case 'json':
                $this->generateJsonReport($results, $reportsDir, $filename);
                break;
            case 'html':
                $this->generateHtmlReport($results, $reportsDir, $filename);
                break;
            case 'txt':
                $this->generateTextReport($results, $reportsDir, $filename);
                break;
            default:
                $this->error("Unsupported export format: {$exportFormat}");
                return;
        }
    }

    /**
     * Generate JSON report
     */
    protected function generateJsonReport(array $results, string $dir, string $filename): void
    {
        $filepath = "{$dir}/{$filename}.json";
        File::put($filepath, json_encode($results, JSON_PRETTY_PRINT));
        $this->info("JSON report generated: {$filepath}");
    }

    /**
     * Generate HTML report
     */
    protected function generateHtmlReport(array $results, string $dir, string $filename): void
    {
        $html = $this->buildHtmlReport($results);
        $filepath = "{$dir}/{$filename}.html";
        File::put($filepath, $html);
        $this->info("HTML report generated: {$filepath}");
    }

    /**
     * Generate text report
     */
    protected function generateTextReport(array $results, string $dir, string $filename): void
    {
        $text = $this->buildTextReport($results);
        $filepath = "{$dir}/{$filename}.txt";
        File::put($filepath, $text);
        $this->info("Text report generated: {$filepath}");
    }

    /**
     * Build HTML report
     */
    protected function buildHtmlReport(array $results): string
    {
        $summary = $results['summary'];
        $auditDate = $results['audit_date'];
        
        $html = "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Security Audit Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .score { font-size: 24px; font-weight: bold; }
        .grade-A { color: #28a745; }
        .grade-B { color: #ffc107; }
        .grade-C { color: #fd7e14; }
        .grade-D, .grade-F { color: #dc3545; }
        .vulnerability { margin: 15px 0; padding: 15px; border-left: 4px solid #ccc; }
        .high { border-left-color: #dc3545; background: #f8d7da; }
        .medium { border-left-color: #ffc107; background: #fff3cd; }
        .low { border-left-color: #17a2b8; background: #d1ecf1; }
        .summary-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .summary-table th, .summary-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .summary-table th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>Security Audit Report</h1>
        <p><strong>Generated:</strong> {$auditDate}</p>
        <div class='score grade-{$summary['security_grade']}'>
            Security Grade: {$summary['security_grade']} ({$summary['security_percentage']}%)
        </div>
    </div>

    <h2>Summary</h2>
    <table class='summary-table'>
        <tr><th>Metric</th><th>Value</th></tr>
        <tr><td>Total Security Checks</td><td>{$summary['total_checks']}</td></tr>
        <tr><td>Vulnerabilities Found</td><td>{$summary['vulnerabilities_found']}</td></tr>
        <tr><td>High Severity</td><td style='color: #dc3545;'>{$summary['high_severity']}</td></tr>
        <tr><td>Medium Severity</td><td style='color: #ffc107;'>{$summary['medium_severity']}</td></tr>
        <tr><td>Low Severity</td><td style='color: #17a2b8;'>{$summary['low_severity']}</td></tr>
    </table>";

        if (!empty($results['vulnerabilities'])) {
            $html .= "<h2>Vulnerabilities</h2>";
            
            foreach ($results['vulnerabilities'] as $index => $vulnerability) {
                $severityClass = strtolower($vulnerability['severity']);
                $html .= "<div class='vulnerability {$severityClass}'>
                    <h3>[{$vulnerability['severity']}] {$vulnerability['type']}</h3>
                    <p><strong>Description:</strong> {$vulnerability['description']}</p>
                    <p><strong>Recommendation:</strong> {$vulnerability['recommendation']}</p>";
                
                if (isset($vulnerability['files']) && !empty($vulnerability['files'])) {
                    $html .= "<p><strong>Affected Files:</strong> " . implode(', ', $vulnerability['files']) . "</p>";
                }
                
                $html .= "</div>";
            }
        }

        $html .= "</body></html>";
        
        return $html;
    }

    /**
     * Build text report
     */
    protected function buildTextReport(array $results): string
    {
        $summary = $results['summary'];
        $auditDate = $results['audit_date'];
        
        $text = "SECURITY AUDIT REPORT\n";
        $text .= "=====================\n\n";
        $text .= "Generated: {$auditDate}\n";
        $text .= "Security Grade: {$summary['security_grade']} ({$summary['security_percentage']}%)\n\n";
        
        $text .= "SUMMARY\n";
        $text .= "-------\n";
        $text .= "Total Security Checks: {$summary['total_checks']}\n";
        $text .= "Vulnerabilities Found: {$summary['vulnerabilities_found']}\n";
        $text .= "High Severity: {$summary['high_severity']}\n";
        $text .= "Medium Severity: {$summary['medium_severity']}\n";
        $text .= "Low Severity: {$summary['low_severity']}\n\n";

        if (!empty($results['vulnerabilities'])) {
            $text .= "VULNERABILITIES\n";
            $text .= "---------------\n\n";
            
            foreach ($results['vulnerabilities'] as $index => $vulnerability) {
                $text .= ($index + 1) . ". [{$vulnerability['severity']}] {$vulnerability['type']}\n";
                $text .= "   Description: {$vulnerability['description']}\n";
                $text .= "   Recommendation: {$vulnerability['recommendation']}\n";
                
                if (isset($vulnerability['files']) && !empty($vulnerability['files'])) {
                    $text .= "   Affected Files: " . implode(', ', $vulnerability['files']) . "\n";
                }
                
                $text .= "\n";
            }
        }

        return $text;
    }

    /**
     * Attempt to fix common security issues
     */
    protected function attemptFixes(array $results): void
    {
        $this->info('Attempting to fix common security issues...');
        
        $fixedIssues = 0;
        
        foreach ($results['vulnerabilities'] as $vulnerability) {
            switch ($vulnerability['type']) {
                case 'File Permissions':
                    if ($this->fixFilePermissions($vulnerability)) {
                        $fixedIssues++;
                    }
                    break;
                    
                case 'Public Directory Security':
                    if ($this->fixPublicDirectorySecurity($vulnerability)) {
                        $fixedIssues++;
                    }
                    break;
                    
                case 'Backup File Security':
                    if ($this->fixBackupFileSecurity($vulnerability)) {
                        $fixedIssues++;
                    }
                    break;
            }
        }

        if ($fixedIssues > 0) {
            $this->info("Fixed {$fixedIssues} security issues.");
        } else {
            $this->warn('No issues could be automatically fixed.');
        }
    }

    /**
     * Fix file permissions
     */
    protected function fixFilePermissions(array $vulnerability): bool
    {
        if (!isset($vulnerability['issues'])) {
            return false;
        }

        $fixed = false;
        
        foreach ($vulnerability['issues'] as $issue) {
            $file = $issue['file'];
            $expected = $issue['expected'];
            $fullPath = base_path($file);
            
            if (File::exists($fullPath)) {
                try {
                    chmod($fullPath, octdec($expected));
                    $this->line("Fixed permissions for: {$file}");
                    $fixed = true;
                } catch (\Exception $e) {
                    $this->warn("Could not fix permissions for {$file}: " . $e->getMessage());
                }
            }
        }

        return $fixed;
    }

    /**
     * Fix public directory security issues
     */
    protected function fixPublicDirectorySecurity(array $vulnerability): bool
    {
        if (!isset($vulnerability['files'])) {
            return false;
        }

        $fixed = false;
        
        foreach ($vulnerability['files'] as $file) {
            $fullPath = public_path($file);
            
            if (File::exists($fullPath)) {
                try {
                    File::delete($fullPath);
                    $this->line("Removed sensitive file from public directory: {$file}");
                    $fixed = true;
                } catch (\Exception $e) {
                    $this->warn("Could not remove file {$file}: " . $e->getMessage());
                }
            }
        }

        return $fixed;
    }

    /**
     * Fix backup file security issues
     */
    protected function fixBackupFileSecurity(array $vulnerability): bool
    {
        if (!isset($vulnerability['files'])) {
            return false;
        }

        $fixed = false;
        $secureBackupDir = storage_path('app/backups');
        
        if (!File::exists($secureBackupDir)) {
            File::makeDirectory($secureBackupDir, 0755, true);
        }

        foreach ($vulnerability['files'] as $file) {
            $sourcePath = public_path($file);
            $targetPath = $secureBackupDir . '/' . basename($file);
            
            if (File::exists($sourcePath)) {
                try {
                    File::move($sourcePath, $targetPath);
                    $this->line("Moved backup file to secure location: {$file}");
                    $fixed = true;
                } catch (\Exception $e) {
                    $this->warn("Could not move backup file {$file}: " . $e->getMessage());
                }
            }
        }

        return $fixed;
    }
}