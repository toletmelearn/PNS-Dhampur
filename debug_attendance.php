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

echo "=== Debugging Attendance Rate Calculation ===\n\n";

// Create test data
$testClass = ClassModel::create(['name' => 'Debug Class']);
echo "Created test class: {$testClass->name} (ID: {$testClass->id})\n";

$student = Student::create([
    'user_id' => 999,
    'name' => 'Debug Student',
    'admission_no' => 'DEBUG1',
    'father_name' => 'Debug Father',
    'mother_name' => 'Debug Mother',
    'dob' => '2010-01-01',
    'class_id' => $testClass->id,
    'verification_status' => 'verified'
]);
echo "Created test student: {$student->name} (ID: {$student->id})\n";

// Create 10 attendance records: 8 present, 2 absent
for ($j = 0; $j < 10; $j++) {
    $attendance = Attendance::create([
        'student_id' => $student->id,
        'class_id' => $testClass->id,
        'date' => Carbon::now()->subDays($j),
        'status' => $j < 8 ? 'present' : 'absent'
    ]);
    echo "Created attendance record {$j}: {$attendance->status} on {$attendance->date}\n";
}

echo "\n=== Raw Attendance Data ===\n";
$attendanceRecords = DB::table('attendances')
    ->where('student_id', $student->id)
    ->orderBy('date')
    ->get();

foreach ($attendanceRecords as $record) {
    echo "Date: {$record->date}, Status: {$record->status}\n";
}

echo "\n=== Manual Calculation ===\n";
$totalRecords = $attendanceRecords->count();
$presentCount = $attendanceRecords->where('status', 'present')->count();
$attendanceRate = ($presentCount / $totalRecords) * 100;

echo "Total Records: {$totalRecords}\n";
echo "Present Count: {$presentCount}\n";
echo "Manual Attendance Rate: {$attendanceRate}%\n";

echo "\n=== OptimizedReportService Result ===\n";
$reportService = new OptimizedReportService();
$result = $reportService->getOptimizedAttendanceCohortAnalysis();
$classData = $result->where('class_id', $testClass->id)->first();

if ($classData) {
    echo "Class Name: {$classData['class_name']}\n";
    echo "Student Count: {$classData['student_count']}\n";
    echo "Total Records: {$classData['total_records']}\n";
    echo "Present Count: {$classData['present_count']}\n";
    echo "Attendance Rate: {$classData['attendance_rate']}%\n";
    echo "Performance Category: {$classData['performance_category']}\n";
} else {
    echo "No class data found!\n";
}

echo "\n=== Raw SQL Query Debug ===\n";
$dateFrom = Carbon::now()->subMonths(3)->format('Y-m-d');
$dateTo = Carbon::now()->format('Y-m-d');

$rawResult = DB::table('class_models')
    ->select([
        'class_models.id as class_id',
        'class_models.name as class_name',
        DB::raw('COUNT(DISTINCT students.id) as student_count'),
        DB::raw('COUNT(attendances.id) as total_attendance_records'),
        DB::raw('SUM(CASE WHEN attendances.status = "present" THEN 1 ELSE 0 END) as present_count'),
        DB::raw('SUM(CASE WHEN attendances.status = "absent" THEN 1 ELSE 0 END) as absent_count'),
        DB::raw('SUM(CASE WHEN attendances.status = "late" THEN 1 ELSE 0 END) as late_count'),
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
    ->where('class_models.id', $testClass->id)
    ->groupBy('class_models.id', 'class_models.name')
    ->first();

if ($rawResult) {
    echo "Raw SQL Result:\n";
    echo "  Class ID: {$rawResult->class_id}\n";
    echo "  Class Name: {$rawResult->class_name}\n";
    echo "  Student Count: {$rawResult->student_count}\n";
    echo "  Total Records: {$rawResult->total_attendance_records}\n";
    echo "  Present Count: {$rawResult->present_count}\n";
    echo "  Absent Count: {$rawResult->absent_count}\n";
    echo "  Attendance Rate: {$rawResult->attendance_rate}%\n";
} else {
    echo "No raw SQL result found!\n";
}

// Clean up
echo "\n=== Cleanup ===\n";
DB::table('attendances')->where('student_id', $student->id)->delete();
$student->delete();
$testClass->delete();
echo "Cleanup completed.\n";