<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ClassDataAudit;
use App\Models\ClassDataApproval;

echo "=== Debug Approval Validation ===\n";

// Create admin user like in test
$adminUser = User::factory()->admin()->create();
echo "Admin user created with ID: " . $adminUser->id . "\n";
echo "Admin user role: " . $adminUser->role . "\n";
echo "Admin user hasRole admin: " . ($adminUser->hasRole(['admin']) ? 'Yes' : 'No') . "\n";

// Create audit and approval like in test
$audit = ClassDataAudit::factory()->create([
    'approval_status' => 'pending',
    'risk_level' => 'high',
    'user_id' => $adminUser->id
]);

$approval = ClassDataApproval::factory()->create([
    'audit_id' => $audit->id,
    'status' => 'pending',
    'assigned_to' => $adminUser->id
]);

echo "Approval created with ID: " . $approval->id . "\n";
echo "Approval status: " . $approval->status . "\n";
echo "Approval assigned_to: " . $approval->assigned_to . "\n";
echo "Admin user ID: " . $adminUser->id . "\n";
echo "Assigned to matches user: " . ($approval->assigned_to === $adminUser->id ? 'Yes' : 'No') . "\n";
echo "User has admin role: " . ($adminUser->hasRole(['admin', 'principal']) ? 'Yes' : 'No') . "\n";

// Test the validation logic from ApprovalActionRequest
$canProcess = $approval->assigned_to === $adminUser->id || $adminUser->hasRole(['admin', 'principal']);
echo "Can process approval: " . ($canProcess ? 'Yes' : 'No') . "\n";

// Check if approval is actually assigned to a different user
echo "\n=== Checking approval assignment ===\n";
$actualApproval = ClassDataApproval::find($approval->id);
echo "Actual approval assigned_to: " . $actualApproval->assigned_to . "\n";
echo "Actual approval status: " . $actualApproval->status . "\n";

// Check if there's a mismatch in user IDs
if ($actualApproval->assigned_to != $adminUser->id) {
    $assignedUser = User::find($actualApproval->assigned_to);
    echo "Approval is assigned to different user: " . ($assignedUser ? $assignedUser->name . " (ID: " . $assignedUser->id . ")" : "Unknown user") . "\n";
}

echo "\nDebug completed.\n";