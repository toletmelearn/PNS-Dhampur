<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\ClassTeacherDataController;
use App\Models\ClassData;
use App\Models\ClassDataAudit;
use App\Models\ClassDataVersion;
use App\Models\ClassDataApproval;
use App\Models\ChangeLog;
use App\Models\User;

echo "=== ClassTeacherDataController Validation ===\n\n";

// Check if all required models exist
$models = [
    'ClassData' => App\Models\ClassData::class,
    'ClassDataAudit' => App\Models\ClassDataAudit::class,
    'ClassDataVersion' => App\Models\ClassDataVersion::class,
    'ClassDataApproval' => App\Models\ClassDataApproval::class,
    'ChangeLog' => App\Models\ChangeLog::class,
    'User' => App\Models\User::class,
];

echo "1. Checking Model Existence:\n";
foreach ($models as $name => $class) {
    if (class_exists($class)) {
        echo "   ✓ {$name} model exists\n";
    } else {
        echo "   ✗ {$name} model missing\n";
    }
}

// Check if controller exists and has required methods
echo "\n2. Checking Controller Methods:\n";
if (class_exists(ClassTeacherDataController::class)) {
    echo "   ✓ ClassTeacherDataController exists\n";
    
    $controller = new ClassTeacherDataController();
    $requiredMethods = [
        'index', 'store', 'show', 'update', 
        'auditTrail', 'history', 'approve', 'reject'
    ];
    
    foreach ($requiredMethods as $method) {
        if (method_exists($controller, $method)) {
            echo "   ✓ {$method}() method exists\n";
        } else {
            echo "   ✗ {$method}() method missing\n";
        }
    }
} else {
    echo "   ✗ ClassTeacherDataController missing\n";
}

// Check model relationships
echo "\n3. Checking Model Relationships:\n";

// ClassData relationships
if (class_exists(App\Models\ClassData::class)) {
    $classData = new App\Models\ClassData();
    
    if (method_exists($classData, 'audits')) {
        echo "   ✓ ClassData has audits() relationship\n";
    } else {
        echo "   ✗ ClassData missing audits() relationship\n";
    }
    
    if (method_exists($classData, 'versions')) {
        echo "   ✓ ClassData has versions() relationship\n";
    } else {
        echo "   ✗ ClassData missing versions() relationship\n";
    }
    
    if (method_exists($classData, 'changeLogs')) {
        echo "   ✓ ClassData has changeLogs() relationship\n";
    } else {
        echo "   ✗ ClassData missing changeLogs() relationship\n";
    }
}

// ClassDataAudit relationships
if (class_exists(App\Models\ClassDataAudit::class)) {
    $audit = new App\Models\ClassDataAudit();
    
    if (method_exists($audit, 'user')) {
        echo "   ✓ ClassDataAudit has user() relationship\n";
    } else {
        echo "   ✗ ClassDataAudit missing user() relationship\n";
    }
    
    if (method_exists($audit, 'approval')) {
        echo "   ✓ ClassDataAudit has approval() relationship\n";
    } else {
        echo "   ✗ ClassDataAudit missing approval() relationship\n";
    }
}

// Check fillable fields
echo "\n4. Checking Model Fillable Fields:\n";

if (class_exists(App\Models\ClassData::class)) {
    $classData = new App\Models\ClassData();
    $fillable = $classData->getFillable();
    $requiredFields = ['class_name', 'subject', 'data', 'metadata', 'status'];
    
    foreach ($requiredFields as $field) {
        if (in_array($field, $fillable)) {
            echo "   ✓ ClassData has {$field} fillable\n";
        } else {
            echo "   ✗ ClassData missing {$field} fillable\n";
        }
    }
}

if (class_exists(App\Models\ClassDataAudit::class)) {
    $audit = new App\Models\ClassDataAudit();
    $fillable = $audit->getFillable();
    $requiredFields = ['auditable_type', 'auditable_id', 'event_type', 'user_id', 'old_values', 'new_values'];
    
    foreach ($requiredFields as $field) {
        if (in_array($field, $fillable)) {
            echo "   ✓ ClassDataAudit has {$field} fillable\n";
        } else {
            echo "   ✗ ClassDataAudit missing {$field} fillable\n";
        }
    }
}

// Check casts
echo "\n5. Checking Model Casts:\n";

if (class_exists(App\Models\ClassData::class)) {
    $classData = new App\Models\ClassData();
    $casts = $classData->getCasts();
    
    if (isset($casts['data']) && $casts['data'] === 'array') {
        echo "   ✓ ClassData casts 'data' as array\n";
    } else {
        echo "   ✗ ClassData missing 'data' array cast\n";
    }
    
    if (isset($casts['metadata']) && $casts['metadata'] === 'array') {
        echo "   ✓ ClassData casts 'metadata' as array\n";
    } else {
        echo "   ✗ ClassData missing 'metadata' array cast\n";
    }
}

if (class_exists(App\Models\ClassDataAudit::class)) {
    $audit = new App\Models\ClassDataAudit();
    $casts = $audit->getCasts();
    
    if (isset($casts['old_values']) && $casts['old_values'] === 'array') {
        echo "   ✓ ClassDataAudit casts 'old_values' as array\n";
    } else {
        echo "   ✗ ClassDataAudit missing 'old_values' array cast\n";
    }
    
    if (isset($casts['new_values']) && $casts['new_values'] === 'array') {
        echo "   ✓ ClassDataAudit casts 'new_values' as array\n";
    } else {
        echo "   ✗ ClassDataAudit missing 'new_values' array cast\n";
    }
}

echo "\n=== Validation Complete ===\n";
echo "If all checks show ✓, the controller and audit trail system are properly configured.\n";
echo "If any checks show ✗, those components need to be fixed.\n\n";