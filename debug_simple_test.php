<?php

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebugClassDataAuditTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->admin()->create();
        
        echo "Admin user created: ID {$this->adminUser->id}, Role: {$this->adminUser->role}\n";
        echo "Admin can access attendance: " . ($this->adminUser->canAccessAttendance() ? 'Yes' : 'No') . "\n";
        echo "Admin has view_audit_trails permission: " . ($this->adminUser->hasPermission('view_audit_trails') ? 'Yes' : 'No') . "\n";
    }

    public function test_debug_admin_access()
    {
        echo "\n=== Testing Admin Access ===\n";
        
        // Act as admin user
        $response = $this->actingAs($this->adminUser)->get('/class-data-audit');
        
        echo "Response status: " . $response->getStatusCode() . "\n";
        
        if ($response->getStatusCode() === 302) {
            echo "Redirect location: " . $response->headers->get('Location') . "\n";
        }
        
        // Check session for errors
        $session = $response->getSession();
        if ($session && $session->has('error')) {
            echo "Session error: " . $session->get('error') . "\n";
        }
        
        // Check if user is authenticated in the response
        echo "User authenticated in response: " . (auth()->check() ? 'Yes' : 'No') . "\n";
        if (auth()->check()) {
            echo "Authenticated user: " . auth()->user()->email . " (Role: " . auth()->user()->role . ")\n";
        }
        
        return $response;
    }
}

// Run the test
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$test = new DebugClassDataAuditTest();
$test->setUp();
$response = $test->test_debug_admin_access();

echo "\nTest completed.\n";