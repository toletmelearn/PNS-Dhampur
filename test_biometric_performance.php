<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\BiometricDevice;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== BiometricDevice Performance Test ===\n\n";

try {
    echo "1. Testing basic device query...\n";
    $startTime = microtime(true);
    
    $query = BiometricDevice::with('registeredBy:id,name');
    $devices = $query->orderBy('device_name')->get();
    
    $queryTime = microtime(true) - $startTime;
    echo "✅ Basic query completed in " . round($queryTime * 1000, 2) . "ms\n";
    echo "Found " . $devices->count() . " device(s)\n\n";
    
    if ($devices->count() > 0) {
        echo "2. Testing individual method performance...\n";
        
        foreach ($devices as $index => $device) {
            echo "Device " . ($index + 1) . ": " . $device->device_name . "\n";
            
            // Test isOnline method
            $startTime = microtime(true);
            try {
                $isOnline = $device->isOnline();
                $methodTime = microtime(true) - $startTime;
                echo "  ✅ isOnline(): " . round($methodTime * 1000, 2) . "ms - Result: " . ($isOnline ? 'true' : 'false') . "\n";
            } catch (Exception $e) {
                echo "  ❌ isOnline() error: " . $e->getMessage() . "\n";
            }
            
            // Test needsMaintenance method
            $startTime = microtime(true);
            try {
                $needsMaintenance = $device->needsMaintenance();
                $methodTime = microtime(true) - $startTime;
                echo "  ✅ needsMaintenance(): " . round($methodTime * 1000, 2) . "ms - Result: " . ($needsMaintenance ? 'true' : 'false') . "\n";
            } catch (Exception $e) {
                echo "  ❌ needsMaintenance() error: " . $e->getMessage() . "\n";
            }
            
            // Test getTodaysScanCount method (this might be the slow one)
            $startTime = microtime(true);
            try {
                $scanCount = $device->getTodaysScanCount();
                $methodTime = microtime(true) - $startTime;
                echo "  ✅ getTodaysScanCount(): " . round($methodTime * 1000, 2) . "ms - Result: " . $scanCount . "\n";
                
                if ($methodTime > 1.0) {
                    echo "  ⚠️  WARNING: getTodaysScanCount() took over 1 second!\n";
                }
            } catch (Exception $e) {
                echo "  ❌ getTodaysScanCount() error: " . $e->getMessage() . "\n";
            }
            
            // Test getUptimePercentage method
            $startTime = microtime(true);
            try {
                $uptime = $device->getUptimePercentage();
                $methodTime = microtime(true) - $startTime;
                echo "  ✅ getUptimePercentage(): " . round($methodTime * 1000, 2) . "ms - Result: " . $uptime . "%\n";
            } catch (Exception $e) {
                echo "  ❌ getUptimePercentage() error: " . $e->getMessage() . "\n";
            }
            
            echo "\n";
            
            // Stop after first device if it's taking too long
            if ($index >= 2) {
                echo "Stopping after 3 devices to avoid timeout...\n";
                break;
            }
        }
    }
    
    echo "\n3. Testing the problematic attendanceRecords relationship...\n";
    
    if ($devices->count() > 0) {
        $device = $devices->first();
        
        echo "Testing attendanceRecords query for device: " . $device->device_name . "\n";
        
        $startTime = microtime(true);
        try {
            $count = $device->attendanceRecords()->count();
            $queryTime = microtime(true) - $startTime;
            echo "✅ Total attendance records: " . $count . " (took " . round($queryTime * 1000, 2) . "ms)\n";
        } catch (Exception $e) {
            echo "❌ attendanceRecords() error: " . $e->getMessage() . "\n";
        }
        
        $startTime = microtime(true);
        try {
            $todayCount = $device->attendanceRecords()
                ->whereDate('created_at', today())
                ->count();
            $queryTime = microtime(true) - $startTime;
            echo "✅ Today's attendance records: " . $todayCount . " (took " . round($queryTime * 1000, 2) . "ms)\n";
            
            if ($queryTime > 1.0) {
                echo "⚠️  WARNING: Today's count query took over 1 second!\n";
            }
        } catch (Exception $e) {
            echo "❌ Today's count error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n4. Testing database connection...\n";
    
    $startTime = microtime(true);
    try {
        $result = \DB::select('SELECT 1 as test');
        $queryTime = microtime(true) - $startTime;
        echo "✅ Database connection test: " . round($queryTime * 1000, 2) . "ms\n";
    } catch (Exception $e) {
        echo "❌ Database connection error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Performance Test Complete ===\n";