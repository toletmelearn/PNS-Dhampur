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

echo "Testing authentication event listeners...\n\n";

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
    
    // Test 1: Login event listener
    echo "=== Testing Login Event Listener ===\n";
    
    // Create a mock request to simulate web environment
    $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
    app()->instance('request', $request);
    
    // Simulate login - this should trigger the Login event
    Auth::login($testUser);
    echo "User logged in via Auth::login() - should trigger Login event\n";
    
    // Wait a moment for event processing
    usleep(100000); // 0.1 seconds
    
    // Check for login audit logs
    $loginAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('event', 'user_login')
        ->latest()
        ->get();
    
    echo "Found " . $loginAudits->count() . " login audit logs from event listener\n";
    foreach ($loginAudits as $audit) {
        echo "- Event: {$audit->event}, IP: {$audit->ip_address}, Time: {$audit->created_at}\n";
        echo "  Additional data: " . json_encode($audit->additional_data) . "\n";
    }
    
    // Check for user session records
    $userSessions = UserSession::where('user_id', $testUser->id)->get();
    echo "Found " . $userSessions->count() . " user session records from event listener\n";
    foreach ($userSessions as $session) {
        echo "- Session ID: {$session->session_id}, IP: {$session->ip_address}, Status: {$session->status}\n";
    }
    
    echo "\n=== Testing Logout Event Listener ===\n";
    
    // Simulate logout - this should trigger the Logout event
    Auth::logout();
    echo "User logged out via Auth::logout() - should trigger Logout event\n";
    
    // Wait a moment for event processing
    usleep(100000); // 0.1 seconds
    
    // Check for logout audit logs
    $logoutAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('event', 'user_logout')
        ->latest()
        ->get();
    
    echo "Found " . $logoutAudits->count() . " logout audit logs from event listener\n";
    foreach ($logoutAudits as $audit) {
        echo "- Event: {$audit->event}, IP: {$audit->ip_address}, Time: {$audit->created_at}\n";
        echo "  Additional data: " . json_encode($audit->additional_data) . "\n";
    }
    
    // Check updated user session records
    $updatedSessions = UserSession::where('user_id', $testUser->id)->get();
    echo "Updated user session records: " . $updatedSessions->count() . "\n";
    foreach ($updatedSessions as $session) {
        echo "- Session ID: {$session->session_id}, Status: {$session->status}, Ended: {$session->ended_at}\n";
    }
    
    echo "\n=== Testing Multiple Login/Logout Cycles with Event Listeners ===\n";
    
    // Test multiple cycles
    for ($i = 1; $i <= 3; $i++) {
        echo "Cycle $i: ";
        Auth::login($testUser);
        echo "Login -> ";
        usleep(50000); // 0.05 seconds
        Auth::logout();
        echo "Logout\n";
        usleep(50000); // 0.05 seconds
    }
    
    // Final audit count
    $totalLoginAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('event', 'user_login')
        ->count();
    
    $totalLogoutAudits = AuditTrail::where('user_id', $testUser->id)
        ->where('event', 'user_logout')
        ->count();
    
    echo "\nFinal Results:\n";
    echo "Total login audit logs: $totalLoginAudits\n";
    echo "Total logout audit logs: $totalLogoutAudits\n";
    
    $finalSessions = UserSession::where('user_id', $testUser->id)->count();
    echo "Total user session records: $finalSessions\n";
    
    // Test event listener registration
    echo "\n=== Testing Event Listener Registration ===\n";
    
    $eventDispatcher = app('events');
    $loginListeners = $eventDispatcher->getListeners('Illuminate\Auth\Events\Login');
    $logoutListeners = $eventDispatcher->getListeners('Illuminate\Auth\Events\Logout');
    
    echo "Registered Login event listeners: " . count($loginListeners) . "\n";
    foreach ($loginListeners as $listener) {
        if (is_array($listener) && isset($listener[0])) {
            echo "- " . get_class($listener[0]) . "\n";
        } else {
            echo "- " . (is_string($listener) ? $listener : 'Unknown listener') . "\n";
        }
    }
    
    echo "Registered Logout event listeners: " . count($logoutListeners) . "\n";
    foreach ($logoutListeners as $listener) {
        if (is_array($listener) && isset($listener[0])) {
            echo "- " . get_class($listener[0]) . "\n";
        } else {
            echo "- " . (is_string($listener) ? $listener : 'Unknown listener') . "\n";
        }
    }
    
    echo "\nAuthentication event listener test completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}