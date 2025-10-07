<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Services\OptimizedReportService;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Attendance;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Debugging Test Issue ===\n\n";

// Check existing classes
echo "=== Existing Classes ===\n";
$existingClasses = DB::table('class_models')->get();
foreach ($existingClasses as $class) {
    echo "Class ID: {$class->id}, Name: {$class->name}, is_active: " . ($class->is_active ?? 'NULL') . "\n";
}

// Create test class exactly like the test
$testClass = ClassModel::create([
    'name' => 'Test Class 10A',
    'is_active' => true
]);
echo "\nCreated test class: {$testClass->name} (ID: {$testClass->id}, is_active: " . ($testClass->is_active ? 'true' : 'false') . ")\n";

// Create one test student
$student = Student::create([
    'user_id' => 999,
    'name' => "Test Student Debug",
    'admission_no' => "TSD999",
    'father_name' => "Father Debug",
    'mother_name' => "Mother Debug",
    'dob' => '2010-01-01',
    'class_id' => $testClass->id,
    'verification_status' => 'verified',
    'status' => 'active'
]);
echo "Created student: {$student->name} (ID: {$student->id}, class_id: {$student->class_id}, status: {$student->status})\n";

// Create attendance records
for ($j = 0; $j < 10; $j++) {
    Attendance::create([
        'student_id' => $student->id,
        'class_id' => $testClass->id,
        'date' => Carbon::now()->subDays($j),
        'status' => $j < 8 ? 'present' : 'absent'
    ]);
}
echo "Created 10 attendance records (8 present, 2 absent)\n";

echo "\n=== Raw Query Test ===\n";
$dateFrom = Carbon::now()->subMonths(3)->format('Y-m-d');
$dateTo = Carbon::now()->format('Y-m-d');

$rawResults = DB::table('class_models')
    ->select([
        'class_models.id as class_id',
        'class_models.name as class_name',
        'class_models.is_active',
        DB::raw('COUNT(DISTINCT students.id) as student_count'),
        DB::raw('COUNT(attendances.id) as total_attendance_records'),
        DB::raw('SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) as present_count'),
        DB::raw('SUM(CASE WHEN attendances.status = "absent" THEN 1 ELSE 0 END) as absent_count'),
        DB::raw('ROUND((SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) / COUNT(attendances.id)) * 100, 2) as attendance_rate')
    ])
    ->leftJoin('students', function ($join) {
        $join->on('class_models.id', '=', 'students.class_id')
             ->where('students.status', '=', 'active');
    })
    ->leftJoin('attendances', function ($join) use ($dateFrom, $dateTo) {
        $join->on('students.id', '=', 'attendances.student_id')
             ->whereBetween('attendances.date', [$dateFrom, $dateTo]);
    })
    ->where('class_models.is_active', true)
    ->groupBy('class_models.id', 'class_models.name', 'class_models.is_active')
    ->orderBy('class_models.name')
    ->get();

echo "Raw query results:\n";
foreach ($rawResults as $result) {
    echo "Class: {$result->class_name} (ID: {$result->class_id}, is_active: {$result->is_active})\n";
    echo "  Student Count: {$result->student_count}\n";
    echo "  Total Records: {$result->total_attendance_records}\n";
    echo "  Present Count: {$result->present_count}\n";
    echo "  Attendance Rate: {$result->attendance_rate}%\n";
}

echo "\n=== OptimizedReportService Result ===\n";
$reportService = new OptimizedReportService();
$result = $reportService->getOptimizedAttendanceCohortAnalysis();

echo "Total classes returned: " . $result->count() . "\n";
foreach ($result as $classData) {
    echo "Class: {$classData['class_name']} (ID: {$classData['class_id']})\n";
    echo "  Student Count: {$classData['student_count']}\n";
    echo "  Total Records: {$classData['total_records']}\n";
    echo "  Present Count: {$classData['present_count']}\n";
    echo "  Attendance Rate: {$classData['attendance_rate']}%\n";
    echo "  Performance Category: {$classData['performance_category']}\n";
}

// Clean up
echo "\n=== Cleanup ===\n";
DB::table('attendances')->where('student_id', $student->id)->delete();
$student->delete();
$testClass->delete();
echo "Cleanup completed.\n";