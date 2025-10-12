<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SecurityAuditCommand extends Command
{
    protected $signature = 'security:audit {--fix : Automatically fix issues where possible}';
    protected $description = 'Perform a comprehensive security audit of the application';

    private $issues = [];
    private $fixableIssues = [];

    public function handle()
    {
        $this->info('Starting security audit...');
        
        $this->checkEnvironmentSecurity();
        $this->checkDatabaseSecurity();
        $this->checkFileSecurity();
        $this->checkCodeSecurity();
        $this->checkConfigurationSecurity();
        
        $this->displayResults();
        
        if ($this->option('fix') && !empty($this->fixableIssues)) {
            $this->fixIssues();
        }
        
        return 0;
    }

    private function checkEnvironmentSecurity()
    {
        $this->info('Checking environment security...');
        
        // Check .env file permissions
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $permissions = substr(sprintf('%o', fileperms($envPath)), -4);
            if ($permissions !== '0600' && $permissions !== '0644') {
                $this->addIssue('Environment', '.env file has insecure permissions: ' . $permissions, 'high');
            }
        }
        
        // Check for debug mode in production
        if (config('app.env') === 'production' && config('app.debug')) {
            $this->addIssue('Environment', 'Debug mode is enabled in production', 'critical', true);
        }
        
        // Check for default APP_KEY
        if (config('app.key') === 'base64:' || empty(config('app.key'))) {
            $this->addIssue('Environment', 'APP_KEY is not set or using default value', 'critical');
        }
        
        // Check HTTPS configuration
        if (config('app.env') === 'production' && !config('app.force_https', false)) {
            $this->addIssue('Environment', 'HTTPS is not enforced in production', 'high', true);
        }
    }

    private function checkDatabaseSecurity()
    {
        $this->info('Checking database security...');
        
        try {
            // Check for default database credentials
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            
            if ($dbUser === 'root' && empty($dbPass)) {
                $this->addIssue('Database', 'Using root user with empty password', 'critical');
            }
            
            // Check for SQL injection vulnerabilities in raw queries
            $this->checkForRawQueries();
            
        } catch (\Exception $e) {
            $this->addIssue('Database', 'Cannot connect to database: ' . $e->getMessage(), 'high');
        }
    }

    private function checkFileSecurity()
    {
        $this->info('Checking file security...');
        
        // Check for sensitive files in public directory
        $publicPath = public_path();
        $sensitiveFiles = ['.env', '.git', 'composer.json', 'artisan'];
        
        foreach ($sensitiveFiles as $file) {
            if (File::exists($publicPath . '/' . $file)) {
                $this->addIssue('File Security', "Sensitive file {$file} found in public directory", 'critical');
            }
        }
        
        // Check storage permissions
        $storagePath = storage_path();
        if (!is_writable($storagePath)) {
            $this->addIssue('File Security', 'Storage directory is not writable', 'medium');
        }
        
        // Check for backup files
        $this->checkForBackupFiles();
    }

    private function checkCodeSecurity()
    {
        $this->info('Checking code security...');
        
        // Check for hardcoded credentials
        $this->checkForHardcodedCredentials();
        
        // Check for unsafe functions
        $this->checkForUnsafeFunctions();
        
        // Check for CSRF protection
        $this->checkCSRFProtection();
        
        // Check for XSS vulnerabilities
        $this->checkXSSVulnerabilities();
    }

    private function checkConfigurationSecurity()
    {
        $this->info('Checking configuration security...');
        
        // Check session configuration
        if (!config('session.secure') && config('app.env') === 'production') {
            $this->addIssue('Configuration', 'Session cookies not marked as secure in production', 'high', true);
        }
        
        if (!config('session.http_only')) {
            $this->addIssue('Configuration', 'Session cookies not marked as HTTP only', 'medium', true);
        }
        
        // Check CORS configuration
        $corsConfig = config('cors');
        if (isset($corsConfig['allowed_origins']) && in_array('*', $corsConfig['allowed_origins'])) {
            $this->addIssue('Configuration', 'CORS allows all origins (*)', 'medium');
        }
    }

    private function checkForRawQueries()
    {
        $files = $this->getPhpFiles(app_path());
        $patterns = [
            '/DB::raw\s*\(\s*["\'].*\$.*["\']/',
            '/whereRaw\s*\(\s*["\'].*\$.*["\']/',
            '/selectRaw\s*\(\s*["\'].*\$.*["\']/',
        ];
        
        foreach ($files as $file) {
            $content = File::get($file);
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $this->addIssue('SQL Injection', "Potential SQL injection in {$file}", 'high');
                }
            }
        }
    }

    private function checkForHardcodedCredentials()
    {
        $files = $this->getPhpFiles(app_path());
        $patterns = [
            '/password\s*=\s*["\'][^"\']{3,}["\']/',
            '/api_key\s*=\s*["\'][^"\']{10,}["\']/',
            '/secret\s*=\s*["\'][^"\']{10,}["\']/',
        ];
        
        foreach ($files as $file) {
            $content = File::get($file);
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $this->addIssue('Hardcoded Credentials', "Potential hardcoded credential in {$file}", 'high');
                }
            }
        }
    }

    private function checkForUnsafeFunctions()
    {
        $files = $this->getPhpFiles(app_path());
        $unsafeFunctions = ['eval', 'exec', 'system', 'shell_exec', 'passthru'];
        
        foreach ($files as $file) {
            $content = File::get($file);
            foreach ($unsafeFunctions as $function) {
                if (strpos($content, $function . '(') !== false) {
                    $this->addIssue('Unsafe Functions', "Unsafe function {$function} used in {$file}", 'high');
                }
            }
        }
    }

    private function checkCSRFProtection()
    {
        $routeFiles = [
            base_path('routes/web.php'),
            base_path('routes/api.php'),
        ];
        
        foreach ($routeFiles as $file) {
            if (File::exists($file)) {
                $content = File::get($file);
                if (strpos($content, 'Route::post') !== false && strpos($content, 'csrf') === false) {
                    $this->addIssue('CSRF', "POST routes without CSRF protection in {$file}", 'medium');
                }
            }
        }
    }

    private function checkXSSVulnerabilities()
    {
        $bladeFiles = File::allFiles(resource_path('views'));
        
        foreach ($bladeFiles as $file) {
            $content = File::get($file->getPathname());
            // Check for unescaped output
            if (preg_match('/\{\!\!\s*\$.*\!\!\}/', $content)) {
                $this->addIssue('XSS', "Unescaped output in {$file->getPathname()}", 'medium');
            }
        }
    }

    private function checkForBackupFiles()
    {
        $backupPatterns = ['*.bak', '*.backup', '*.old', '*.tmp'];
        
        foreach ($backupPatterns as $pattern) {
            $files = glob(base_path($pattern));
            foreach ($files as $file) {
                $this->addIssue('File Security', "Backup file found: {$file}", 'low');
            }
        }
    }

    private function getPhpFiles($directory)
    {
        return File::allFiles($directory);
    }

    private function addIssue($category, $description, $severity, $fixable = false)
    {
        $issue = [
            'category' => $category,
            'description' => $description,
            'severity' => $severity,
            'fixable' => $fixable,
        ];
        
        $this->issues[] = $issue;
        
        if ($fixable) {
            $this->fixableIssues[] = $issue;
        }
    }

    private function displayResults()
    {
        $this->info("\n" . str_repeat('=', 60));
        $this->info('SECURITY AUDIT RESULTS');
        $this->info(str_repeat('=', 60));
        
        if (empty($this->issues)) {
            $this->info('✅ No security issues found!');
            return;
        }
        
        $severityCounts = array_count_values(array_column($this->issues, 'severity'));
        
        $this->info("Total issues found: " . count($this->issues));
        $this->error("Critical: " . ($severityCounts['critical'] ?? 0));
        $this->warn("High: " . ($severityCounts['high'] ?? 0));
        $this->comment("Medium: " . ($severityCounts['medium'] ?? 0));
        $this->line("Low: " . ($severityCounts['low'] ?? 0));
        
        $this->info("\nDetailed Issues:");
        $this->info(str_repeat('-', 60));
        
        foreach ($this->issues as $issue) {
            $icon = match($issue['severity']) {
                'critical' => '🔴',
                'high' => '🟠',
                'medium' => '🟡',
                'low' => '🔵',
                default => '⚪'
            };
            
            $fixable = $issue['fixable'] ? ' [FIXABLE]' : '';
            $this->line("{$icon} [{$issue['category']}] {$issue['description']}{$fixable}");
        }
        
        if (!empty($this->fixableIssues)) {
            $this->info("\n" . count($this->fixableIssues) . " issues can be automatically fixed with --fix option");
        }
    }

    private function fixIssues()
    {
        $this->info('Fixing issues...');
        
        foreach ($this->fixableIssues as $issue) {
            switch ($issue['category']) {
                case 'Environment':
                    if (str_contains($issue['description'], 'Debug mode')) {
                        $this->fixDebugMode();
                    } elseif (str_contains($issue['description'], 'HTTPS')) {
                        $this->fixHTTPS();
                    }
                    break;
                    
                case 'Configuration':
                    if (str_contains($issue['description'], 'Session cookies')) {
                        $this->fixSessionSecurity();
                    }
                    break;
            }
        }
        
        $this->info('Automatic fixes applied where possible.');
    }

    private function fixDebugMode()
    {
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $content = File::get($envPath);
            $content = preg_replace('/APP_DEBUG=true/', 'APP_DEBUG=false', $content);
            File::put($envPath, $content);
            $this->info('✅ Fixed: Debug mode disabled');
        }
    }

    private function fixHTTPS()
    {
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $content = File::get($envPath);
            if (!str_contains($content, 'FORCE_HTTPS')) {
                $content .= "\nFORCE_HTTPS=true\n";
                File::put($envPath, $content);
                $this->info('✅ Fixed: HTTPS enforcement enabled');
            }
        }
    }

    private function fixSessionSecurity()
    {
        $configPath = config_path('session.php');
        if (File::exists($configPath)) {
            $content = File::get($configPath);
            $content = preg_replace("/'secure' => false/", "'secure' => env('SESSION_SECURE_COOKIE', true)", $content);
            $content = preg_replace("/'http_only' => false/", "'http_only' => true", $content);
            File::put($configPath, $content);
            $this->info('✅ Fixed: Session security improved');
        }
    }
}