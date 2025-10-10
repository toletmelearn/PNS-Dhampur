<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\User;
use App\Models\AuditTrail;

class SecurityAuditService
{
    protected array $vulnerabilities = [];
    protected array $recommendations = [];
    protected array $securityScore = [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'warnings' => 0
    ];

    /**
     * Run comprehensive security audit
     */
    public function runSecurityAudit(): array
    {
        $this->vulnerabilities = [];
        $this->recommendations = [];
        $this->securityScore = ['total' => 0, 'passed' => 0, 'failed' => 0, 'warnings' => 0];

        // Run all security checks
        $this->checkDatabaseSecurity();
        $this->checkFilePermissions();
        $this->checkConfigurationSecurity();
        $this->checkUserAccountSecurity();
        $this->checkSessionSecurity();
        $this->checkInputValidationSecurity();
        $this->checkEncryptionSecurity();
        $this->checkLoggingSecurity();
        $this->checkDependencySecurity();
        $this->checkServerSecurity();

        // Calculate security score
        $this->calculateSecurityScore();

        return [
            'audit_date' => Carbon::now()->toDateTimeString(),
            'security_score' => $this->securityScore,
            'vulnerabilities' => $this->vulnerabilities,
            'recommendations' => $this->recommendations,
            'summary' => $this->generateSummary()
        ];
    }

    /**
     * Check database security
     */
    protected function checkDatabaseSecurity(): void
    {
        $this->securityScore['total'] += 5;

        // Check for SQL injection vulnerabilities
        $this->checkSqlInjectionPatterns();

        // Check database configuration
        $this->checkDatabaseConfiguration();

        // Check for sensitive data exposure
        $this->checkSensitiveDataExposure();

        // Check database user privileges
        $this->checkDatabasePrivileges();

        // Check for database backup security
        $this->checkDatabaseBackupSecurity();
    }

    /**
     * Check for SQL injection patterns in code
     */
    protected function checkSqlInjectionPatterns(): void
    {
        $controllerPath = app_path('Http/Controllers');
        $modelPath = app_path('Models');
        
        $suspiciousPatterns = [
            'DB::raw\(\$',
            'whereRaw\(\$',
            'havingRaw\(\$',
            'orderByRaw\(\$',
            'selectRaw\(\$'
        ];

        $vulnerableFiles = [];

        foreach ([$controllerPath, $modelPath] as $path) {
            if (File::exists($path)) {
                $files = File::allFiles($path);
                
                foreach ($files as $file) {
                    $content = File::get($file->getPathname());
                    
                    foreach ($suspiciousPatterns as $pattern) {
                        if (preg_match('/' . $pattern . '/', $content)) {
                            $vulnerableFiles[] = $file->getRelativePathname();
                            break;
                        }
                    }
                }
            }
        }

        if (!empty($vulnerableFiles)) {
            $this->vulnerabilities[] = [
                'type' => 'SQL Injection Risk',
                'severity' => 'HIGH',
                'description' => 'Potential SQL injection vulnerabilities found in files',
                'files' => $vulnerableFiles,
                'recommendation' => 'Use parameterized queries and avoid raw SQL with user input'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check database configuration security
     */
    protected function checkDatabaseConfiguration(): void
    {
        $dbConfig = Config::get('database.connections.' . Config::get('database.default'));
        
        $issues = [];

        // Check if using default credentials
        if (in_array($dbConfig['username'] ?? '', ['root', 'admin', 'sa'])) {
            $issues[] = 'Using default database username';
        }

        // Check if password is weak or empty
        $password = $dbConfig['password'] ?? '';
        if (empty($password) || strlen($password) < 8) {
            $issues[] = 'Weak or empty database password';
        }

        // Check if using default port
        $defaultPorts = ['3306', '5432', '1433'];
        if (in_array($dbConfig['port'] ?? '', $defaultPorts)) {
            $issues[] = 'Using default database port';
        }

        if (!empty($issues)) {
            $this->vulnerabilities[] = [
                'type' => 'Database Configuration',
                'severity' => 'MEDIUM',
                'description' => 'Database configuration security issues',
                'issues' => $issues,
                'recommendation' => 'Use strong credentials and non-default ports'
            ];
            $this->securityScore['warnings']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check for sensitive data exposure
     */
    protected function checkSensitiveDataExposure(): void
    {
        // Check for sensitive fields without encryption
        $sensitiveFields = ['password', 'ssn', 'credit_card', 'bank_account'];
        $tables = $this->getAllTables();
        
        $exposedFields = [];

        foreach ($tables as $table) {
            try {
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                
                foreach ($columns as $column) {
                    foreach ($sensitiveFields as $sensitive) {
                        if (stripos($column, $sensitive) !== false && $column !== 'password') {
                            $exposedFields[] = "{$table}.{$column}";
                        }
                    }
                }
            } catch (\Exception $e) {
                // Skip if table doesn't exist or can't be accessed
            }
        }

        if (!empty($exposedFields)) {
            $this->vulnerabilities[] = [
                'type' => 'Sensitive Data Exposure',
                'severity' => 'HIGH',
                'description' => 'Potentially sensitive fields found without encryption',
                'fields' => $exposedFields,
                'recommendation' => 'Encrypt sensitive data fields'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check database privileges
     */
    protected function checkDatabasePrivileges(): void
    {
        try {
            if (DB::getDriverName() === 'mysql') {
                $privileges = DB::select('SHOW GRANTS');
                
                $hasAllPrivileges = false;
                foreach ($privileges as $privilege) {
                    $grant = array_values((array)$privilege)[0];
                    if (stripos($grant, 'ALL PRIVILEGES') !== false) {
                        $hasAllPrivileges = true;
                        break;
                    }
                }

                if ($hasAllPrivileges) {
                    $this->vulnerabilities[] = [
                        'type' => 'Database Privileges',
                        'severity' => 'MEDIUM',
                        'description' => 'Database user has ALL PRIVILEGES',
                        'recommendation' => 'Use principle of least privilege for database access'
                    ];
                    $this->securityScore['warnings']++;
                } else {
                    $this->securityScore['passed']++;
                }
            } else {
                $this->securityScore['passed']++;
            }
        } catch (\Exception $e) {
            $this->securityScore['warnings']++;
        }
    }

    /**
     * Check database backup security
     */
    protected function checkDatabaseBackupSecurity(): void
    {
        $backupPath = storage_path('app/backups');
        
        if (File::exists($backupPath)) {
            $backupFiles = File::files($backupPath);
            $unencryptedBackups = [];

            foreach ($backupFiles as $file) {
                // Check if backup file is encrypted (simple check for .enc extension or encrypted content)
                if (!str_ends_with($file->getFilename(), '.enc') && 
                    !str_ends_with($file->getFilename(), '.gpg')) {
                    $unencryptedBackups[] = $file->getFilename();
                }
            }

            if (!empty($unencryptedBackups)) {
                $this->vulnerabilities[] = [
                    'type' => 'Backup Security',
                    'severity' => 'MEDIUM',
                    'description' => 'Unencrypted database backups found',
                    'files' => $unencryptedBackups,
                    'recommendation' => 'Encrypt database backups'
                ];
                $this->securityScore['warnings']++;
            } else {
                $this->securityScore['passed']++;
            }
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check file permissions
     */
    protected function checkFilePermissions(): void
    {
        $this->securityScore['total'] += 3;

        $criticalFiles = [
            '.env' => '600',
            'storage' => '755',
            'bootstrap/cache' => '755'
        ];

        $permissionIssues = [];

        foreach ($criticalFiles as $file => $expectedPerm) {
            $fullPath = base_path($file);
            
            if (File::exists($fullPath)) {
                $currentPerm = substr(sprintf('%o', fileperms($fullPath)), -3);
                
                if ($currentPerm !== $expectedPerm) {
                    $permissionIssues[] = [
                        'file' => $file,
                        'current' => $currentPerm,
                        'expected' => $expectedPerm
                    ];
                }
            }
        }

        if (!empty($permissionIssues)) {
            $this->vulnerabilities[] = [
                'type' => 'File Permissions',
                'severity' => 'MEDIUM',
                'description' => 'Incorrect file permissions detected',
                'issues' => $permissionIssues,
                'recommendation' => 'Set correct file permissions for security'
            ];
            $this->securityScore['warnings']++;
        } else {
            $this->securityScore['passed']++;
        }

        // Check for sensitive files in public directory
        $this->checkPublicDirectorySecurity();

        // Check for backup files in accessible locations
        $this->checkBackupFileSecurity();
    }

    /**
     * Check public directory security
     */
    protected function checkPublicDirectorySecurity(): void
    {
        $publicPath = public_path();
        $sensitiveExtensions = ['.env', '.sql', '.bak', '.log', '.config'];
        
        $exposedFiles = [];

        if (File::exists($publicPath)) {
            $files = File::allFiles($publicPath);
            
            foreach ($files as $file) {
                foreach ($sensitiveExtensions as $ext) {
                    if (str_ends_with($file->getFilename(), $ext)) {
                        $exposedFiles[] = $file->getRelativePathname();
                    }
                }
            }
        }

        if (!empty($exposedFiles)) {
            $this->vulnerabilities[] = [
                'type' => 'Public Directory Security',
                'severity' => 'HIGH',
                'description' => 'Sensitive files found in public directory',
                'files' => $exposedFiles,
                'recommendation' => 'Remove sensitive files from public directory'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check backup file security
     */
    protected function checkBackupFileSecurity(): void
    {
        $backupExtensions = ['.bak', '.backup', '.sql', '.dump'];
        $webAccessiblePaths = [public_path(), resource_path('views')];
        
        $exposedBackups = [];

        foreach ($webAccessiblePaths as $path) {
            if (File::exists($path)) {
                $files = File::allFiles($path);
                
                foreach ($files as $file) {
                    foreach ($backupExtensions as $ext) {
                        if (str_ends_with($file->getFilename(), $ext)) {
                            $exposedBackups[] = $file->getRelativePathname();
                        }
                    }
                }
            }
        }

        if (!empty($exposedBackups)) {
            $this->vulnerabilities[] = [
                'type' => 'Backup File Security',
                'severity' => 'HIGH',
                'description' => 'Backup files found in web-accessible locations',
                'files' => $exposedBackups,
                'recommendation' => 'Move backup files to secure, non-web-accessible locations'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check configuration security
     */
    protected function checkConfigurationSecurity(): void
    {
        $this->securityScore['total'] += 4;

        // Check APP_DEBUG setting
        if (Config::get('app.debug') === true) {
            $this->vulnerabilities[] = [
                'type' => 'Configuration Security',
                'severity' => 'HIGH',
                'description' => 'APP_DEBUG is enabled in production',
                'recommendation' => 'Set APP_DEBUG=false in production environment'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }

        // Check APP_KEY
        $appKey = Config::get('app.key');
        if (empty($appKey) || $appKey === 'base64:' || strlen($appKey) < 32) {
            $this->vulnerabilities[] = [
                'type' => 'Configuration Security',
                'severity' => 'HIGH',
                'description' => 'Weak or missing APP_KEY',
                'recommendation' => 'Generate a strong APP_KEY using php artisan key:generate'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }

        // Check HTTPS configuration
        if (!Config::get('app.force_https', false)) {
            $this->vulnerabilities[] = [
                'type' => 'Configuration Security',
                'severity' => 'MEDIUM',
                'description' => 'HTTPS not enforced',
                'recommendation' => 'Enable HTTPS enforcement for all requests'
            ];
            $this->securityScore['warnings']++;
        } else {
            $this->securityScore['passed']++;
        }

        // Check session configuration
        $this->checkSessionConfiguration();
    }

    /**
     * Check session configuration
     */
    protected function checkSessionConfiguration(): void
    {
        $sessionConfig = Config::get('session');
        $issues = [];

        // Check secure flag
        if (!$sessionConfig['secure']) {
            $issues[] = 'Session secure flag not set';
        }

        // Check httponly flag
        if (!$sessionConfig['http_only']) {
            $issues[] = 'Session httponly flag not set';
        }

        // Check same_site setting
        if ($sessionConfig['same_site'] !== 'strict') {
            $issues[] = 'Session same_site not set to strict';
        }

        if (!empty($issues)) {
            $this->vulnerabilities[] = [
                'type' => 'Session Configuration',
                'severity' => 'MEDIUM',
                'description' => 'Session security configuration issues',
                'issues' => $issues,
                'recommendation' => 'Configure secure session settings'
            ];
            $this->securityScore['warnings']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check user account security
     */
    protected function checkUserAccountSecurity(): void
    {
        $this->securityScore['total'] += 3;

        // Check for weak passwords
        $this->checkWeakPasswords();

        // Check for inactive admin accounts
        $this->checkInactiveAdminAccounts();

        // Check for default accounts
        $this->checkDefaultAccounts();
    }

    /**
     * Check for weak passwords
     */
    protected function checkWeakPasswords(): void
    {
        $weakPasswords = ['password', '123456', 'admin', 'test'];
        $vulnerableUsers = [];

        foreach ($weakPasswords as $weakPassword) {
            $users = User::where('password', Hash::make($weakPassword))->get();
            foreach ($users as $user) {
                $vulnerableUsers[] = $user->email;
            }
        }

        if (!empty($vulnerableUsers)) {
            $this->vulnerabilities[] = [
                'type' => 'Weak Passwords',
                'severity' => 'HIGH',
                'description' => 'Users with weak passwords detected',
                'users' => $vulnerableUsers,
                'recommendation' => 'Enforce strong password policy'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check for inactive admin accounts
     */
    protected function checkInactiveAdminAccounts(): void
    {
        $inactiveAdmins = User::where('role', 'admin')
            ->where('last_login_at', '<', Carbon::now()->subDays(90))
            ->orWhereNull('last_login_at')
            ->get();

        if ($inactiveAdmins->count() > 0) {
            $this->vulnerabilities[] = [
                'type' => 'Inactive Admin Accounts',
                'severity' => 'MEDIUM',
                'description' => 'Inactive admin accounts found',
                'count' => $inactiveAdmins->count(),
                'recommendation' => 'Disable or remove inactive admin accounts'
            ];
            $this->securityScore['warnings']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check for default accounts
     */
    protected function checkDefaultAccounts(): void
    {
        $defaultEmails = ['admin@admin.com', 'test@test.com', 'demo@demo.com'];
        $defaultAccounts = User::whereIn('email', $defaultEmails)->get();

        if ($defaultAccounts->count() > 0) {
            $this->vulnerabilities[] = [
                'type' => 'Default Accounts',
                'severity' => 'HIGH',
                'description' => 'Default user accounts found',
                'accounts' => $defaultAccounts->pluck('email')->toArray(),
                'recommendation' => 'Remove or secure default accounts'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check session security
     */
    protected function checkSessionSecurity(): void
    {
        $this->securityScore['total'] += 2;

        // Check session lifetime
        $sessionLifetime = Config::get('session.lifetime');
        if ($sessionLifetime > 120) { // More than 2 hours
            $this->vulnerabilities[] = [
                'type' => 'Session Security',
                'severity' => 'MEDIUM',
                'description' => 'Session lifetime too long',
                'current_lifetime' => $sessionLifetime,
                'recommendation' => 'Reduce session lifetime for better security'
            ];
            $this->securityScore['warnings']++;
        } else {
            $this->securityScore['passed']++;
        }

        // Check session driver security
        $sessionDriver = Config::get('session.driver');
        if ($sessionDriver === 'file') {
            $this->vulnerabilities[] = [
                'type' => 'Session Security',
                'severity' => 'LOW',
                'description' => 'Using file-based sessions',
                'recommendation' => 'Consider using database or Redis for session storage'
            ];
            $this->securityScore['warnings']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check input validation security
     */
    protected function checkInputValidationSecurity(): void
    {
        $this->securityScore['total'] += 2;

        // Check for XSS vulnerabilities
        $this->checkXssVulnerabilities();

        // Check CSRF protection
        $this->checkCsrfProtection();
    }

    /**
     * Check for XSS vulnerabilities
     */
    protected function checkXssVulnerabilities(): void
    {
        $viewPath = resource_path('views');
        $xssVulnerabilities = [];

        if (File::exists($viewPath)) {
            $files = File::allFiles($viewPath);
            
            foreach ($files as $file) {
                $content = File::get($file->getPathname());
                
                // Check for unescaped output
                if (preg_match('/\{\!\!\s*\$/', $content)) {
                    $xssVulnerabilities[] = $file->getRelativePathname();
                }
            }
        }

        if (!empty($xssVulnerabilities)) {
            $this->vulnerabilities[] = [
                'type' => 'XSS Vulnerabilities',
                'severity' => 'HIGH',
                'description' => 'Potential XSS vulnerabilities found in views',
                'files' => $xssVulnerabilities,
                'recommendation' => 'Use escaped output {{ }} instead of {!! !!}'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check CSRF protection
     */
    protected function checkCsrfProtection(): void
    {
        $middlewareGroups = Config::get('app.middleware_groups', []);
        $webMiddleware = $middlewareGroups['web'] ?? [];
        
        $hasCsrfProtection = in_array('App\Http\Middleware\VerifyCsrfToken', $webMiddleware) ||
                           in_array(\App\Http\Middleware\VerifyCsrfToken::class, $webMiddleware);

        if (!$hasCsrfProtection) {
            $this->vulnerabilities[] = [
                'type' => 'CSRF Protection',
                'severity' => 'HIGH',
                'description' => 'CSRF protection not enabled',
                'recommendation' => 'Enable CSRF protection middleware'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check encryption security
     */
    protected function checkEncryptionSecurity(): void
    {
        $this->securityScore['total'] += 1;

        $cipher = Config::get('app.cipher');
        if ($cipher !== 'AES-256-CBC') {
            $this->vulnerabilities[] = [
                'type' => 'Encryption Security',
                'severity' => 'MEDIUM',
                'description' => 'Weak encryption cipher',
                'current_cipher' => $cipher,
                'recommendation' => 'Use AES-256-CBC encryption'
            ];
            $this->securityScore['warnings']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check logging security
     */
    protected function checkLoggingSecurity(): void
    {
        $this->securityScore['total'] += 1;

        $logPath = storage_path('logs');
        $publicLogFiles = [];

        // Check if log files are in public directory
        $publicPath = public_path();
        if (File::exists($publicPath)) {
            $files = File::allFiles($publicPath);
            
            foreach ($files as $file) {
                if (str_ends_with($file->getFilename(), '.log')) {
                    $publicLogFiles[] = $file->getRelativePathname();
                }
            }
        }

        if (!empty($publicLogFiles)) {
            $this->vulnerabilities[] = [
                'type' => 'Logging Security',
                'severity' => 'HIGH',
                'description' => 'Log files found in public directory',
                'files' => $publicLogFiles,
                'recommendation' => 'Move log files to secure location'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check dependency security
     */
    protected function checkDependencySecurity(): void
    {
        $this->securityScore['total'] += 1;

        $composerLock = base_path('composer.lock');
        if (File::exists($composerLock)) {
            $lockData = json_decode(File::get($composerLock), true);
            $packages = $lockData['packages'] ?? [];
            
            $outdatedPackages = [];
            foreach ($packages as $package) {
                // Simple check for very old packages (this is basic, real security audit would use vulnerability databases)
                $version = $package['version'] ?? '';
                if (preg_match('/^v?(\d+)\./', $version, $matches)) {
                    $majorVersion = (int)$matches[1];
                    if ($majorVersion < 2 && !str_contains($package['name'], 'laravel')) {
                        $outdatedPackages[] = $package['name'] . ':' . $version;
                    }
                }
            }

            if (!empty($outdatedPackages)) {
                $this->vulnerabilities[] = [
                    'type' => 'Dependency Security',
                    'severity' => 'MEDIUM',
                    'description' => 'Potentially outdated packages found',
                    'packages' => array_slice($outdatedPackages, 0, 10), // Limit to first 10
                    'recommendation' => 'Update packages and run security audit'
                ];
                $this->securityScore['warnings']++;
            } else {
                $this->securityScore['passed']++;
            }
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Check server security
     */
    protected function checkServerSecurity(): void
    {
        $this->securityScore['total'] += 2;

        // Check PHP version
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.0.0', '<')) {
            $this->vulnerabilities[] = [
                'type' => 'Server Security',
                'severity' => 'HIGH',
                'description' => 'Outdated PHP version',
                'current_version' => $phpVersion,
                'recommendation' => 'Update to PHP 8.0 or higher'
            ];
            $this->securityScore['failed']++;
        } else {
            $this->securityScore['passed']++;
        }

        // Check dangerous PHP functions
        $dangerousFunctions = ['exec', 'shell_exec', 'system', 'passthru', 'eval'];
        $enabledDangerous = [];

        foreach ($dangerousFunctions as $function) {
            if (function_exists($function)) {
                $enabledDangerous[] = $function;
            }
        }

        if (!empty($enabledDangerous)) {
            $this->vulnerabilities[] = [
                'type' => 'Server Security',
                'severity' => 'MEDIUM',
                'description' => 'Dangerous PHP functions enabled',
                'functions' => $enabledDangerous,
                'recommendation' => 'Disable dangerous PHP functions if not needed'
            ];
            $this->securityScore['warnings']++;
        } else {
            $this->securityScore['passed']++;
        }
    }

    /**
     * Calculate security score
     */
    protected function calculateSecurityScore(): void
    {
        if ($this->securityScore['total'] > 0) {
            $score = (($this->securityScore['passed'] + ($this->securityScore['warnings'] * 0.5)) / $this->securityScore['total']) * 100;
            $this->securityScore['percentage'] = round($score, 1);
            
            if ($score >= 90) {
                $this->securityScore['grade'] = 'A';
            } elseif ($score >= 80) {
                $this->securityScore['grade'] = 'B';
            } elseif ($score >= 70) {
                $this->securityScore['grade'] = 'C';
            } elseif ($score >= 60) {
                $this->securityScore['grade'] = 'D';
            } else {
                $this->securityScore['grade'] = 'F';
            }
        }
    }

    /**
     * Generate audit summary
     */
    protected function generateSummary(): array
    {
        $highSeverity = collect($this->vulnerabilities)->where('severity', 'HIGH')->count();
        $mediumSeverity = collect($this->vulnerabilities)->where('severity', 'MEDIUM')->count();
        $lowSeverity = collect($this->vulnerabilities)->where('severity', 'LOW')->count();

        return [
            'total_checks' => $this->securityScore['total'],
            'vulnerabilities_found' => count($this->vulnerabilities),
            'high_severity' => $highSeverity,
            'medium_severity' => $mediumSeverity,
            'low_severity' => $lowSeverity,
            'security_grade' => $this->securityScore['grade'] ?? 'N/A',
            'security_percentage' => $this->securityScore['percentage'] ?? 0,
            'recommendations_count' => count($this->recommendations)
        ];
    }

    /**
     * Get all database tables
     */
    protected function getAllTables(): array
    {
        try {
            $driver = DB::getDriverName();
            
            switch ($driver) {
                case 'mysql':
                    $tables = DB::select('SHOW TABLES');
                    $key = 'Tables_in_' . DB::getDatabaseName();
                    return array_map(function($table) use ($key) {
                        return $table->$key;
                    }, $tables);
                    
                case 'pgsql':
                    $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                    return array_map(function($table) {
                        return $table->tablename;
                    }, $tables);
                    
                case 'sqlite':
                    $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                    return array_map(function($table) {
                        return $table->name;
                    }, $tables);
                    
                default:
                    return [];
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Log security audit results
     */
    public function logAuditResults(array $results): void
    {
        Log::info('Security audit completed', [
            'security_score' => $results['security_score'],
            'vulnerabilities_count' => count($results['vulnerabilities']),
            'high_severity_count' => $results['summary']['high_severity']
        ]);

        // Log high severity vulnerabilities separately
        foreach ($results['vulnerabilities'] as $vulnerability) {
            if ($vulnerability['severity'] === 'HIGH') {
                Log::warning('High severity vulnerability found', $vulnerability);
            }
        }
    }
}