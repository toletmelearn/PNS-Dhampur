<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$response = $kernel->handle($request);

echo "=== PRODUCTION READINESS VERIFICATION ===\n\n";

// 1. CONFIGURATION FILES CHECK
function checkConfigurationFiles() {
    echo "⚙️ CONFIGURATION FILES CHECK\n";
    echo str_repeat("=", 50) . "\n";
    
    $configFiles = [
        '.env' => 'Environment Configuration',
        'config/app.php' => 'Application Configuration',
        'config/database.php' => 'Database Configuration',
        'config/security.php' => 'Security Configuration',
        'config/production.php' => 'Production Configuration',
        'config/monitoring.php' => 'Monitoring Configuration',
        'composer.json' => 'Dependencies Configuration',
        'package.json' => 'Frontend Dependencies'
    ];
    
    $results = [];
    $validConfigs = 0;
    $totalConfigs = count($configFiles);
    
    foreach ($configFiles as $file => $description) {
        $exists = file_exists($file);
        $readable = $exists && is_readable($file);
        $hasContent = $readable && filesize($file) > 0;
        
        if ($exists && $readable && $hasContent) {
            echo "✅ {$description} - {$file}\n";
            $validConfigs++;
            $results[$file] = ['valid' => true, 'size' => filesize($file)];
        } else {
            $status = !$exists ? 'Missing' : (!$readable ? 'Not readable' : 'Empty');
            echo "❌ {$description} - {$file} ({$status})\n";
            $results[$file] = ['valid' => false, 'reason' => $status];
        }
    }
    
    $configRate = ($validConfigs / $totalConfigs) * 100;
    echo "\n📊 Configuration Summary: {$validConfigs}/{$totalConfigs} files valid (" . round($configRate, 2) . "%)\n";
    
    return [
        'config_rate' => $configRate,
        'results' => $results,
        'total_configs' => $totalConfigs,
        'valid_configs' => $validConfigs
    ];
}

// 2. DOCUMENTATION CHECK
function checkDocumentation() {
    echo "\n📚 DOCUMENTATION CHECK\n";
    echo str_repeat("=", 50) . "\n";
    
    $docFiles = [
        'README.md' => 'Project README',
        'DEPLOYMENT_GUIDE.md' => 'Deployment Guide',
        'API_DOCUMENTATION.md' => 'API Documentation',
        'ARCHITECTURE_PLAN.md' => 'Architecture Plan',
        'SYSTEM_HANDOVER.md' => 'System Handover',
        'MONITORING_SETUP.md' => 'Monitoring Setup',
        'PRODUCTION_VERIFICATION_REPORT.md' => 'Production Report',
        'docs/ADMIN_GUIDE.md' => 'Admin Guide',
        'docs/USER_GUIDE.md' => 'User Guide'
    ];
    
    $results = [];
    $validDocs = 0;
    $totalDocs = count($docFiles);
    
    foreach ($docFiles as $file => $description) {
        $exists = file_exists($file);
        $hasContent = $exists && filesize($file) > 100; // At least 100 bytes
        
        if ($exists && $hasContent) {
            $size = round(filesize($file) / 1024, 1); // Size in KB
            echo "✅ {$description} - {$file} ({$size}KB)\n";
            $validDocs++;
            $results[$file] = ['valid' => true, 'size_kb' => $size];
        } else {
            $status = !$exists ? 'Missing' : 'Too small/empty';
            echo "❌ {$description} - {$file} ({$status})\n";
            $results[$file] = ['valid' => false, 'reason' => $status];
        }
    }
    
    $docRate = ($validDocs / $totalDocs) * 100;
    echo "\n📊 Documentation Summary: {$validDocs}/{$totalDocs} files complete (" . round($docRate, 2) . "%)\n";
    
    return [
        'doc_rate' => $docRate,
        'results' => $results,
        'total_docs' => $totalDocs,
        'valid_docs' => $validDocs
    ];
}

// 3. DEPLOYMENT SCRIPTS CHECK
function checkDeploymentScripts() {
    echo "\n🚀 DEPLOYMENT SCRIPTS CHECK\n";
    echo str_repeat("=", 50) . "\n";
    
    $deploymentFiles = [
        'deploy.sh' => 'Linux Deployment Script',
        'deploy.ps1' => 'Windows Deployment Script',
        'docker-compose.yml' => 'Docker Compose Configuration',
        'Dockerfile' => 'Docker Configuration',
        'start-server.bat' => 'Server Start Script',
        '.github/workflows' => 'CI/CD Workflows'
    ];
    
    $results = [];
    $validScripts = 0;
    $totalScripts = count($deploymentFiles);
    
    foreach ($deploymentFiles as $file => $description) {
        $exists = file_exists($file) || is_dir($file);
        $hasContent = false;
        
        if ($exists) {
            if (is_dir($file)) {
                $hasContent = count(scandir($file)) > 2; // More than . and ..
            } else {
                $hasContent = filesize($file) > 0;
            }
        }
        
        if ($exists && $hasContent) {
            echo "✅ {$description} - {$file}\n";
            $validScripts++;
            $results[$file] = ['valid' => true];
        } else {
            $status = !$exists ? 'Missing' : 'Empty';
            echo "❌ {$description} - {$file} ({$status})\n";
            $results[$file] = ['valid' => false, 'reason' => $status];
        }
    }
    
    $scriptRate = ($validScripts / $totalScripts) * 100;
    echo "\n📊 Deployment Scripts Summary: {$validScripts}/{$totalScripts} scripts available (" . round($scriptRate, 2) . "%)\n";
    
    return [
        'script_rate' => $scriptRate,
        'results' => $results,
        'total_scripts' => $totalScripts,
        'valid_scripts' => $validScripts
    ];
}

// 4. SECURITY CONFIGURATION CHECK
function checkSecurityConfiguration() {
    echo "\n🔒 SECURITY CONFIGURATION CHECK\n";
    echo str_repeat("=", 50) . "\n";
    
    $securityChecks = [
        'app_key' => [
            'description' => 'Application Key Set',
            'check' => function() {
                $envContent = file_exists('.env') ? file_get_contents('.env') : '';
                return strpos($envContent, 'APP_KEY=') !== false && 
                       strpos($envContent, 'APP_KEY=base64:') !== false;
            }
        ],
        'debug_disabled' => [
            'description' => 'Debug Mode Disabled',
            'check' => function() {
                $envContent = file_exists('.env') ? file_get_contents('.env') : '';
                return strpos($envContent, 'APP_DEBUG=false') !== false;
            }
        ],
        'https_config' => [
            'description' => 'HTTPS Configuration',
            'check' => function() {
                $envContent = file_exists('.env') ? file_get_contents('.env') : '';
                return strpos($envContent, 'FORCE_HTTPS=true') !== false ||
                       strpos($envContent, 'APP_URL=https://') !== false;
            }
        ],
        'session_security' => [
            'description' => 'Session Security Configuration',
            'check' => function() {
                $sessionConfig = file_exists('config/session.php') ? 
                    file_get_contents('config/session.php') : '';
                return strpos($sessionConfig, 'secure') !== false &&
                       strpos($sessionConfig, 'http_only') !== false;
            }
        ],
        'csrf_protection' => [
            'description' => 'CSRF Protection Enabled',
            'check' => function() {
                $kernelFile = file_exists('app/Http/Kernel.php') ? 
                    file_get_contents('app/Http/Kernel.php') : '';
                return strpos($kernelFile, 'VerifyCsrfToken') !== false;
            }
        ]
    ];
    
    $results = [];
    $secureConfigs = 0;
    $totalChecks = count($securityChecks);
    
    foreach ($securityChecks as $key => $check) {
        try {
            $description = $check['description'];
            $isSecure = $check['check']();
            
            if ($isSecure) {
                echo "✅ {$description}\n";
                $secureConfigs++;
                $results[$key] = ['secure' => true];
            } else {
                echo "⚠️  {$description} - Needs configuration\n";
                $results[$key] = ['secure' => false];
            }
            
        } catch (Exception $e) {
            echo "❌ {$description} - ERROR: " . $e->getMessage() . "\n";
            $results[$key] = ['secure' => false, 'error' => $e->getMessage()];
        }
    }
    
    $securityRate = ($secureConfigs / $totalChecks) * 100;
    echo "\n📊 Security Summary: {$secureConfigs}/{$totalChecks} configurations secure (" . round($securityRate, 2) . "%)\n";
    
    return [
        'security_rate' => $securityRate,
        'results' => $results,
        'total_checks' => $totalChecks,
        'secure_configs' => $secureConfigs
    ];
}

// 5. PERFORMANCE OPTIMIZATION CHECK
function checkPerformanceOptimization() {
    echo "\n⚡ PERFORMANCE OPTIMIZATION CHECK\n";
    echo str_repeat("=", 50) . "\n";
    
    $optimizations = [
        'route_cache' => [
            'description' => 'Route Cache',
            'file' => 'bootstrap/cache/routes-v7.php'
        ],
        'config_cache' => [
            'description' => 'Config Cache',
            'file' => 'bootstrap/cache/config.php'
        ],
        'view_cache' => [
            'description' => 'View Cache',
            'file' => 'storage/framework/views'
        ],
        'composer_optimized' => [
            'description' => 'Composer Autoloader Optimized',
            'file' => 'vendor/composer/autoload_classmap.php'
        ],
        'opcache_config' => [
            'description' => 'OPCache Configuration',
            'file' => 'opcache_config.ini'
        ]
    ];
    
    $results = [];
    $optimizedItems = 0;
    $totalOptimizations = count($optimizations);
    
    foreach ($optimizations as $key => $optimization) {
        $description = $optimization['description'];
        $file = $optimization['file'];
        
        $exists = file_exists($file) || is_dir($file);
        $hasContent = false;
        
        if ($exists) {
            if (is_dir($file)) {
                $hasContent = count(scandir($file)) > 2;
            } else {
                $hasContent = filesize($file) > 0;
            }
        }
        
        if ($exists && $hasContent) {
            echo "✅ {$description} - Optimized\n";
            $optimizedItems++;
            $results[$key] = ['optimized' => true];
        } else {
            echo "⚠️  {$description} - Not optimized\n";
            $results[$key] = ['optimized' => false];
        }
    }
    
    $optimizationRate = ($optimizedItems / $totalOptimizations) * 100;
    echo "\n📊 Performance Summary: {$optimizedItems}/{$totalOptimizations} optimizations active (" . round($optimizationRate, 2) . "%)\n";
    
    return [
        'optimization_rate' => $optimizationRate,
        'results' => $results,
        'total_optimizations' => $totalOptimizations,
        'optimized_items' => $optimizedItems
    ];
}

// Run all production readiness checks
echo "Starting production readiness verification...\n\n";

$configResults = checkConfigurationFiles();
$docResults = checkDocumentation();
$scriptResults = checkDeploymentScripts();
$securityResults = checkSecurityConfiguration();
$performanceResults = checkPerformanceOptimization();

// Final Production Readiness Summary
echo "\n" . str_repeat("=", 80) . "\n";
echo "PRODUCTION READINESS SUMMARY\n";
echo str_repeat("=", 80) . "\n";

echo "⚙️ CONFIGURATION FILES:\n";
echo "  Completion Rate: " . round($configResults['config_rate'], 2) . "%\n";
echo "  Valid Files: {$configResults['valid_configs']}/{$configResults['total_configs']}\n";

echo "\n📚 DOCUMENTATION:\n";
echo "  Completion Rate: " . round($docResults['doc_rate'], 2) . "%\n";
echo "  Complete Docs: {$docResults['valid_docs']}/{$docResults['total_docs']}\n";

echo "\n🚀 DEPLOYMENT SCRIPTS:\n";
echo "  Availability Rate: " . round($scriptResults['script_rate'], 2) . "%\n";
echo "  Available Scripts: {$scriptResults['valid_scripts']}/{$scriptResults['total_scripts']}\n";

echo "\n🔒 SECURITY CONFIGURATION:\n";
echo "  Security Rate: " . round($securityResults['security_rate'], 2) . "%\n";
echo "  Secure Configs: {$securityResults['secure_configs']}/{$securityResults['total_checks']}\n";

echo "\n⚡ PERFORMANCE OPTIMIZATION:\n";
echo "  Optimization Rate: " . round($performanceResults['optimization_rate'], 2) . "%\n";
echo "  Optimized Items: {$performanceResults['optimized_items']}/{$performanceResults['total_optimizations']}\n";

// Calculate overall production readiness score
$overallReadinessScore = (
    $configResults['config_rate'] +
    $docResults['doc_rate'] +
    $scriptResults['script_rate'] +
    $securityResults['security_rate'] +
    $performanceResults['optimization_rate']
) / 5;

echo "\n📊 OVERALL PRODUCTION READINESS SCORE: " . round($overallReadinessScore, 2) . "%\n";

if ($overallReadinessScore >= 80) {
    echo "🎉 EXCELLENT! System is fully ready for production deployment!\n";
    echo "🚀 DEPLOYMENT STATUS: ✅ APPROVED FOR PRODUCTION\n";
} elseif ($overallReadinessScore >= 60) {
    echo "✅ GOOD! System is ready for production with minor improvements.\n";
    echo "🚀 DEPLOYMENT STATUS: ✅ APPROVED WITH RECOMMENDATIONS\n";
} else {
    echo "⚠️  WARNING! System needs improvements before production deployment.\n";
    echo "🚀 DEPLOYMENT STATUS: ⚠️ REQUIRES IMPROVEMENTS\n";
}

echo "\n🔍 PRODUCTION READINESS RECOMMENDATIONS:\n";

if ($configResults['config_rate'] < 80) {
    echo "  • Complete missing configuration files\n";
}

if ($docResults['doc_rate'] < 80) {
    echo "  • Complete documentation for deployment and maintenance\n";
}

if ($scriptResults['script_rate'] < 80) {
    echo "  • Prepare deployment and automation scripts\n";
}

if ($securityResults['security_rate'] < 80) {
    echo "  • Enhance security configurations for production\n";
}

if ($performanceResults['optimization_rate'] < 80) {
    echo "  • Apply performance optimizations (caching, etc.)\n";
}

echo str_repeat("=", 80) . "\n";

?>