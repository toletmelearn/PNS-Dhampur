<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DATABASE TABLES CHECK ===\n\n";

try {
    $tables = DB::select('SHOW TABLES');
    $tableNames = [];
    
    foreach ($tables as $table) {
        $tableName = array_values((array)$table)[0];
        $tableNames[] = $tableName;
    }
    
    sort($tableNames);
    
    echo "Total tables found: " . count($tableNames) . "\n\n";
    echo "Tables in database:\n";
    foreach ($tableNames as $table) {
        echo "- $table\n";
    }
    
    echo "\n=== CHECKING SPECIFIC FUNCTIONALITY TABLES ===\n";
    
    $expectedTables = [
        'users', 'roles', 'permissions', 'model_has_roles', 'model_has_permissions',
        'schools', 'students', 'teachers', 'classes', 'subjects',
        'fees', 'fee_structures', 'fee_payments',
        'teacher_documents', 'document_verifications',
        'student_verifications', 'verification_logs',
        'bell_timings', 'seasonal_schedules',
        'teacher_salaries', 'salary_calculations', 'payslips',
        'teacher_experiences', 'career_histories',
        'attendances', 'student_attendances', 'attendance_reports',
        'teacher_substitutions', 'substitution_logs',
        'results', 'grade_calculations', 'result_cards',
        'admit_cards', 'exam_schedules',
        'biometric_attendances', 'time_logs',
        'inventories', 'stock_movements', 'asset_tracking',
        'budgets', 'budget_allocations', 'expense_tracking',
        'exam_papers', 'question_banks', 'paper_approvals',
        'syllabi', 'daily_work_uploads', 'curriculum_tracking',
        'class_teacher_data', 'data_audit_trails',
        'sr_registers', 'student_records', 'academic_histories',
        'alumni', 'alumni_tracking', 'career_updates'
    ];
    
    $existingTables = [];
    $missingTables = [];
    
    foreach ($expectedTables as $table) {
        if (in_array($table, $tableNames)) {
            $existingTables[] = $table;
        } else {
            $missingTables[] = $table;
        }
    }
    
    echo "\nExisting expected tables (" . count($existingTables) . "):\n";
    foreach ($existingTables as $table) {
        echo "✅ $table\n";
    }
    
    echo "\nMissing expected tables (" . count($missingTables) . "):\n";
    foreach ($missingTables as $table) {
        echo "❌ $table\n";
    }
    
    $completionRate = (count($existingTables) / count($expectedTables)) * 100;
    echo "\nDatabase completion rate: " . number_format($completionRate, 1) . "%\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}