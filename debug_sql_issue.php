<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SQL Debug Analysis ===\n";

// Check attendance data
$attendanceData = DB::table('attendances')
    ->select('student_id', 'status', 'date')
    ->whereIn('student_id', function($query) {
        $query->select('id')->from('students')->where('class_id', 1);
    })
    ->get();

echo "Attendance records found: " . $attendanceData->count() . "\n";
foreach ($attendanceData as $record) {
    echo "Student {$record->student_id}: {$record->status} on {$record->date}\n";
}

// Check date filtering
$dateFrom = Carbon::now()->subMonths(3)->format('Y-m-d');
$dateTo = Carbon::now()->format('Y-m-d');
echo "\nDate range: {$dateFrom} to {$dateTo}\n";

$filteredData = DB::table('attendances')
    ->whereIn('student_id', function($query) {
        $query->select('id')->from('students')->where('class_id', 1);
    })
    ->whereBetween('date', [$dateFrom, $dateTo])
    ->get();

echo "Filtered attendance records: " . $filteredData->count() . "\n";

// Test the exact query logic
$result = DB::table('attendances')
    ->select([
        DB::raw('COUNT(id) as total_records'),
        DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count'),
        DB::raw('ROUND(CASE WHEN COUNT(id) > 0 THEN (SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) / COUNT(id)) * 100 ELSE 0 END, 2) as attendance_rate')
    ])
    ->whereIn('student_id', function($query) {
        $query->select('id')->from('students')->where('class_id', 1);
    })
    ->whereBetween('date', [$dateFrom, $dateTo])
    ->first();

echo "\nDirect calculation:\n";
echo "Total records: {$result->total_records}\n";
echo "Present count: {$result->present_count}\n";
echo "Attendance rate: {$result->attendance_rate}%\n";