<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

// Create a new database connection
$capsule = new DB;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'pns_dhampur',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== Testing Attendance Calculation ===\n";

// Test the exact query from the service
$result = DB::selectOne("
    SELECT 
        COUNT(attendances.id) as total,
        SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present,
        CASE WHEN COUNT(attendances.id) > 0 
            THEN ROUND((CAST(SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) AS DECIMAL(10,2)) / CAST(COUNT(attendances.id) AS DECIMAL(10,2))) * 100, 2) 
            ELSE 0 
        END as rate 
    FROM attendances 
    WHERE date BETWEEN '2025-07-07' AND '2025-10-07'
");

echo "Direct attendance query:\n";
echo "Total: {$result->total}\n";
echo "Present: {$result->present}\n";
echo "Rate: {$result->rate}\n\n";

// Test with the full query structure
$fullResult = DB::selectOne("
    SELECT 
        class_models.id as class_id,
        class_models.name as class_name,
        COUNT(DISTINCT students.id) as student_count,
        COUNT(attendances.id) as total_attendance_records,
        SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late_count,
        CASE WHEN COUNT(attendances.id) > 0 
            THEN ROUND((CAST(SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) AS DECIMAL(10,2)) / CAST(COUNT(attendances.id) AS DECIMAL(10,2))) * 100, 2) 
            ELSE 0 
        END as attendance_rate
    FROM class_models
    LEFT JOIN students ON class_models.id = students.class_id
    LEFT JOIN attendances ON students.id = attendances.student_id 
        AND attendances.date BETWEEN '2025-07-07' AND '2025-10-07'
    WHERE class_models.is_active = 1 
        AND students.status = 'active'
    GROUP BY class_models.id, class_models.name
    ORDER BY class_models.name ASC
    LIMIT 1
");

echo "Full query result:\n";
echo "Class ID: {$fullResult->class_id}\n";
echo "Class Name: {$fullResult->class_name}\n";
echo "Student Count: {$fullResult->student_count}\n";
echo "Total Records: {$fullResult->total_attendance_records}\n";
echo "Present Count: {$fullResult->present_count}\n";
echo "Absent Count: {$fullResult->absent_count}\n";
echo "Late Count: {$fullResult->late_count}\n";
echo "Attendance Rate: {$fullResult->attendance_rate}\n";

echo "\n=== Debug Complete ===\n";