<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AuditTrail;
use App\Models\UserSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

echo "Testing user session tracking during login/logout...\n\n";

try {
    // Clear existing audit logs for clean test
    AuditTrail::where('event', 'LIKE', '%login%')->orWhere('event', 'LIKE', '%logout%')->delete();
    
    // Check if UserSession table exists before truncating
    if (Schema::hasTable('user_sessions')) {
        UserSession::truncate();
        echo "Cleared existing session logs for clean test\n";
    } else {
        echo "UserSession table not found, skipping session cleanup\n";
    }
    
    // Find or create a test user
    $testUser = User::where('role', 'admin')->first();
    if (!$testUser) {
        $testUser = User::factory()->admin()->create();
        echo "Created new test user with ID: " . $testUser->id . "\n";
    } else {
        echo "Using existing test user with ID: " . $testUser->id . "\n";
    }
    
    echo "Test user: " . $testUser->name . " (Role: " . $testUser->role . ")\n\n";
    
    // Test 1: Login tracking
    echo "=== Testing Login Tracking ===\n";
    
    // Simulate login
    Auth::login($testUser);
    echo "User logged in via Auth::login()\n";
    
    // Check for login audit logs
    $loginAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('event', 'LIKE', '%login%')
        ->latest()
        ->get();
    
    echo "Found " . $loginAudits->count() . " login audit logs\n";
    foreach ($loginAudits as $audit) {
        echo "- Event: {$audit->event}, IP: {$audit->ip_address}, Time: {$audit->created_at}\n";
    }
    
    // Check for user session records
    $userSessions = UserSession::where('user_id', $testUser->id)->get();
    echo "Found " . $userSessions->count() . " user session records\n";
    foreach ($userSessions as $session) {
        echo "- Session ID: {$session->session_id}, IP: {$session->ip_address}, Status: {$session->status}\n";
    }
    
    echo "\n=== Testing Logout Tracking ===\n";
    
    // Simulate logout
    Auth::logout();
    echo "User logged out via Auth::logout()\n";
    
    // Check for logout audit logs
    $logoutAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('event', 'LIKE', '%logout%')
        ->latest()
        ->get();
    
    echo "Found " . $logoutAudits->count() . " logout audit logs\n";
    foreach ($logoutAudits as $audit) {
        echo "- Event: {$audit->event}, IP: {$audit->ip_address}, Time: {$audit->created_at}\n";
    }
    
    // Check updated user session records
    $updatedSessions = UserSession::where('user_id', $testUser->id)->get();
    echo "Updated user session records: " . $updatedSessions->count() . "\n";
    foreach ($updatedSessions as $session) {
        echo "- Session ID: {$session->session_id}, Status: {$session->status}, Ended: {$session->ended_at}\n";
    }
    
    echo "\n=== Testing Multiple Login/Logout Cycles ===\n";
    
    // Test multiple cycles
    for ($i = 1; $i <= 3; $i++) {
        echo "Cycle $i: ";
        Auth::login($testUser);
        echo "Login -> ";
        Auth::logout();
        echo "Logout\n";
    }
    
    // Final audit count
    $totalLoginAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('event', 'LIKE', '%login%')
        ->count();
    
    $totalLogoutAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('event', 'LIKE', '%logout%')
        ->count();
    
    echo "\nFinal Results:\n";
    echo "Total login audit logs: $totalLoginAudits\n";
    echo "Total logout audit logs: $totalLogoutAudits\n";
    
    $finalSessions = UserSession::where('user_id', $testUser->id)->count();
    echo "Total user session records: $finalSessions\n";
    
    // Test session tracking with different IPs
    echo "\n=== Testing Session Tracking with Different IPs ===\n";
    
    // Mock different IP addresses
    $ips = ['192.168.1.100', '10.0.0.50', '172.16.0.25'];
    
    foreach ($ips as $ip) {
        echo "Testing with IP: $ip\n";
        
        // Create a mock request with specific IP
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $ip]);
        app()->instance('request', $request);
        
        Auth::login($testUser);
        Auth::logout();
    }
    
    // Check if different IPs are tracked
    $ipAudits = AuditTrail::where('user_id', $testUser->id)
        ->whereIn('ip_address', $ips)
        ->get();
    
    echo "Audit logs with different IPs: " . $ipAudits->count() . "\n";
    foreach ($ipAudits->groupBy('ip_address') as $ip => $audits) {
        echo "- IP $ip: " . $audits->count() . " logs\n";
    }
    
    echo "\nSession tracking test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}