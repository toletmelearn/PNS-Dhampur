<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Services\OptimizedReportService;
use App\Models\ClassModel;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\Result;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Debugging Test Data Setup ===\n\n";

// Create test class exactly like the test
$testClass = ClassModel::create([
    'name' => 'Test Class 10A',
    'is_active' => true
]);
echo "Created test class: {$testClass->name} (ID: {$testClass->id}, is_active: " . ($testClass->is_active ? 'true' : 'false') . ")\n";

// Create test students exactly like the test
$testStudents = collect();
for ($i = 1; $i <= 5; $i++) {
    $student = Student::create([
        'user_id' => $i,
        'name' => "Test Student {$i}",
        'admission_no' => "TS{$i}",
        'father_name' => "Father {$i}",
        'mother_name' => "Mother {$i}",
        'dob' => '2010-01-01',
        'class_id' => $testClass->id,
        'verification_status' => 'verified',
        'status' => 'active'
    ]);
    $testStudents->push($student);
    echo "Created student: {$student->name} (ID: {$student->id}, status: {$student->status})\n";

    // Create fees for each student
    Fee::create([
        'student_id' => $student->id,
        'amount' => 1000,
        'paid_amount' => $i * 200, // Varying paid amounts
        'status' => $i <= 3 ? 'partial' : 'unpaid'
    ]);

    // Create attendance records exactly like the test
    for ($j = 0; $j < 10; $j++) {
        $attendance = Attendance::create([
            'student_id' => $student->id,
            'class_id' => $testClass->id,
            'date' => Carbon::now()->subDays($j),
            'status' => $j < 8 ? 'present' : 'absent'
        ]);
        if ($i == 1) { // Only show for first student to avoid clutter
            echo "  Attendance {$j}: {$attendance->status} on {$attendance->date}\n";
        }
    }

    // Create results
    Result::create([
        'student_id' => $student->id,
        'exam_id' => 1,
        'subject' => 'Mathematics',
        'marks_obtained' => 70 + ($i * 5),
        'total_marks' => 100
    ]);
}

echo "\n=== Attendance Summary ===\n";
$totalAttendanceRecords = DB::table('attendances')->where('class_id', $testClass->id)->count();
$presentRecords = DB::table('attendances')->where('class_id', $testClass->id)->where('status', 'present')->count();
$absentRecords = DB::table('attendances')->where('class_id', $testClass->id)->where('status', 'absent')->count();

echo "Total attendance records: {$totalAttendanceRecords}\n";
echo "Present records: {$presentRecords}\n";
echo "Absent records: {$absentRecords}\n";
echo "Manual attendance rate: " . (($presentRecords / $totalAttendanceRecords) * 100) . "%\n";

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

echo "\n=== Date Range Check ===\n";
$dateFrom = Carbon::now()->subMonths(3)->format('Y-m-d');
$dateTo = Carbon::now()->format('Y-m-d');
echo "Date range: {$dateFrom} to {$dateTo}\n";

$attendanceInRange = DB::table('attendances')
    ->where('class_id', $testClass->id)
    ->whereBetween('date', [$dateFrom, $dateTo])
    ->count();
echo "Attendance records in date range: {$attendanceInRange}\n";

// Clean up
echo "\n=== Cleanup ===\n";
DB::table('attendances')->where('class_id', $testClass->id)->delete();
DB::table('fees')->whereIn('student_id', $testStudents->pluck('id'))->delete();
DB::table('results')->whereIn('student_id', $testStudents->pluck('id'))->delete();
foreach ($testStudents as $student) {
    $student->delete();
}
$testClass->delete();
echo "Cleanup completed.\n";