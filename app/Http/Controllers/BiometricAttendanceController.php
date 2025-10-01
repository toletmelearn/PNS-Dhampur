<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\BiometricAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BiometricAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $teacherId = $request->get('teacher_id');
        
        $query = BiometricAttendance::with(['teacher'])
            ->whereDate('date', $date);
            
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        
        $attendances = $query->orderBy('check_in_time')->get();
        $teachers = Teacher::orderBy('name')->get();
        
        // Calculate summary statistics
        $totalTeachers = Teacher::count();
        $presentTeachers = $attendances->where('status', 'present')->count();
        $absentTeachers = $totalTeachers - $presentTeachers;
        $lateArrivals = $attendances->where('is_late', true)->count();
        
        $summary = [
            'total_teachers' => $totalTeachers,
            'present' => $presentTeachers,
            'absent' => $absentTeachers,
            'late_arrivals' => $lateArrivals,
            'attendance_percentage' => $totalTeachers > 0 ? round(($presentTeachers / $totalTeachers) * 100, 2) : 0
        ];
        
        return view('biometric-attendance.index', compact('attendances', 'teachers', 'date', 'summary'));
    }

    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:teachers,id',
            'biometric_data' => 'nullable|string',
            'device_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $teacher = Teacher::findOrFail($request->teacher_id);
            $today = now()->format('Y-m-d');
            $currentTime = now();
            
            // Check if already checked in today
            $existingAttendance = BiometricAttendance::where('teacher_id', $request->teacher_id)
                ->whereDate('date', $today)
                ->first();
                
            if ($existingAttendance && $existingAttendance->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher has already checked in today at ' . $existingAttendance->check_in_time->format('H:i:s')
                ], 400);
            }

            // Define school timing (can be made configurable)
            $schoolStartTime = Carbon::createFromFormat('H:i:s', '08:00:00');
            $isLate = $currentTime->format('H:i:s') > $schoolStartTime->format('H:i:s');
            
            // Create or update attendance record
            $attendance = BiometricAttendance::updateOrCreate(
                [
                    'teacher_id' => $request->teacher_id,
                    'date' => $today
                ],
                [
                    'check_in_time' => $currentTime,
                    'status' => 'present',
                    'is_late' => $isLate,
                    'biometric_data' => $request->biometric_data,
                    'device_id' => $request->device_id,
                    'check_in_location' => $request->location ?? 'Main Campus'
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful' . ($isLate ? ' (Late arrival)' : ''),
                'data' => [
                    'teacher' => $teacher->name,
                    'check_in_time' => $attendance->check_in_time->format('H:i:s'),
                    'is_late' => $isLate,
                    'status' => 'checked_in'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:teachers,id',
            'biometric_data' => 'nullable|string',
            'device_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $teacher = Teacher::findOrFail($request->teacher_id);
            $today = now()->format('Y-m-d');
            $currentTime = now();
            
            // Find today's attendance record
            $attendance = BiometricAttendance::where('teacher_id', $request->teacher_id)
                ->whereDate('date', $today)
                ->first();
                
            if (!$attendance || !$attendance->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher has not checked in today. Please check in first.'
                ], 400);
            }
            
            if ($attendance->check_out_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher has already checked out today at ' . $attendance->check_out_time->format('H:i:s')
                ], 400);
            }

            // Calculate working hours
            $checkInTime = Carbon::parse($attendance->check_in_time);
            $workingHours = $checkInTime->diffInMinutes($currentTime) / 60;
            
            // Define minimum working hours (can be made configurable)
            $minimumHours = 8;
            $isEarlyDeparture = $workingHours < $minimumHours;
            
            // Update attendance record
            $attendance->update([
                'check_out_time' => $currentTime,
                'working_hours' => round($workingHours, 2),
                'is_early_departure' => $isEarlyDeparture,
                'check_out_location' => $request->location ?? 'Main Campus'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-out successful' . ($isEarlyDeparture ? ' (Early departure)' : ''),
                'data' => [
                    'teacher' => $teacher->name,
                    'check_out_time' => $attendance->check_out_time->format('H:i:s'),
                    'working_hours' => $attendance->working_hours,
                    'is_early_departure' => $isEarlyDeparture,
                    'status' => 'checked_out'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Check-out failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function markAbsent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:teachers,id',
            'date' => 'required|date',
            'reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $teacher = Teacher::findOrFail($request->teacher_id);
            
            // Check if attendance record already exists
            $existingAttendance = BiometricAttendance::where('teacher_id', $request->teacher_id)
                ->whereDate('date', $request->date)
                ->first();
                
            if ($existingAttendance && $existingAttendance->check_in_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot mark as absent. Teacher has already checked in.'
                ], 400);
            }

            // Create or update attendance record
            $attendance = BiometricAttendance::updateOrCreate(
                [
                    'teacher_id' => $request->teacher_id,
                    'date' => $request->date
                ],
                [
                    'status' => 'absent',
                    'absence_reason' => $request->reason,
                    'marked_by' => auth()->id()
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Teacher marked as absent successfully',
                'data' => [
                    'teacher' => $teacher->name,
                    'date' => $request->date,
                    'status' => 'absent',
                    'reason' => $request->reason
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark absent: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTeacherStatus($teacherId)
    {
        try {
            $teacher = Teacher::findOrFail($teacherId);
            $today = now()->format('Y-m-d');
            
            $attendance = BiometricAttendance::where('teacher_id', $teacherId)
                ->whereDate('date', $today)
                ->first();
                
            $status = 'not_checked_in';
            $data = ['teacher' => $teacher->name];
            
            if ($attendance) {
                if ($attendance->status === 'absent') {
                    $status = 'absent';
                    $data['reason'] = $attendance->absence_reason;
                } elseif ($attendance->check_out_time) {
                    $status = 'checked_out';
                    $data['check_in_time'] = $attendance->check_in_time->format('H:i:s');
                    $data['check_out_time'] = $attendance->check_out_time->format('H:i:s');
                    $data['working_hours'] = $attendance->working_hours;
                } elseif ($attendance->check_in_time) {
                    $status = 'checked_in';
                    $data['check_in_time'] = $attendance->check_in_time->format('H:i:s');
                    $data['is_late'] = $attendance->is_late;
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => array_merge($data, ['status' => $status])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get teacher status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDailyReport(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        
        $attendances = BiometricAttendance::with(['teacher'])
            ->whereDate('date', $date)
            ->get();
            
        $allTeachers = Teacher::all();
        
        // Prepare report data
        $report = [];
        foreach ($allTeachers as $teacher) {
            $attendance = $attendances->where('teacher_id', $teacher->id)->first();
            
            $report[] = [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'employee_id' => $teacher->employee_id,
                'department' => $teacher->department,
                'status' => $attendance ? $attendance->status : 'absent',
                'check_in_time' => $attendance && $attendance->check_in_time ? $attendance->check_in_time->format('H:i:s') : null,
                'check_out_time' => $attendance && $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : null,
                'working_hours' => $attendance ? $attendance->working_hours : 0,
                'is_late' => $attendance ? $attendance->is_late : false,
                'is_early_departure' => $attendance ? $attendance->is_early_departure : false,
                'absence_reason' => $attendance ? $attendance->absence_reason : null
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'report' => $report,
                'summary' => [
                    'total_teachers' => $allTeachers->count(),
                    'present' => $attendances->where('status', 'present')->count(),
                    'absent' => $allTeachers->count() - $attendances->where('status', 'present')->count(),
                    'late_arrivals' => $attendances->where('is_late', true)->count(),
                    'early_departures' => $attendances->where('is_early_departure', true)->count()
                ]
            ]
        ]);
    }

    public function getMonthlyReport(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $teacherId = $request->get('teacher_id');
        
        $query = BiometricAttendance::with(['teacher'])
            ->whereYear('date', Carbon::parse($month)->year)
            ->whereMonth('date', Carbon::parse($month)->month);
            
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        
        $attendances = $query->get();
        $teachers = Teacher::when($teacherId, function($q) use ($teacherId) {
            return $q->where('id', $teacherId);
        })->get();
        
        // Prepare monthly report
        $report = [];
        foreach ($teachers as $teacher) {
            $teacherAttendances = $attendances->where('teacher_id', $teacher->id);
            
            $report[] = [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'employee_id' => $teacher->employee_id,
                'total_days' => $teacherAttendances->count(),
                'present_days' => $teacherAttendances->where('status', 'present')->count(),
                'absent_days' => $teacherAttendances->where('status', 'absent')->count(),
                'late_arrivals' => $teacherAttendances->where('is_late', true)->count(),
                'early_departures' => $teacherAttendances->where('is_early_departure', true)->count(),
                'total_working_hours' => $teacherAttendances->sum('working_hours'),
                'average_working_hours' => $teacherAttendances->where('working_hours', '>', 0)->avg('working_hours'),
                'attendance_percentage' => $teacherAttendances->count() > 0 ? 
                    round(($teacherAttendances->where('status', 'present')->count() / $teacherAttendances->count()) * 100, 2) : 0
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'report' => $report
            ]
        ]);
    }

    public function bulkCheckIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'exists:teachers,id',
            'device_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = [];
            $today = now()->format('Y-m-d');
            $currentTime = now();
            $schoolStartTime = Carbon::createFromFormat('H:i:s', '08:00:00');
            $isLate = $currentTime->format('H:i:s') > $schoolStartTime->format('H:i:s');
            
            foreach ($request->teacher_ids as $teacherId) {
                $teacher = Teacher::find($teacherId);
                
                // Check if already checked in
                $existingAttendance = BiometricAttendance::where('teacher_id', $teacherId)
                    ->whereDate('date', $today)
                    ->first();
                    
                if ($existingAttendance && $existingAttendance->check_in_time) {
                    $results[] = [
                        'teacher_id' => $teacherId,
                        'teacher_name' => $teacher->name,
                        'success' => false,
                        'message' => 'Already checked in'
                    ];
                    continue;
                }
                
                // Create attendance record
                BiometricAttendance::updateOrCreate(
                    [
                        'teacher_id' => $teacherId,
                        'date' => $today
                    ],
                    [
                        'check_in_time' => $currentTime,
                        'status' => 'present',
                        'is_late' => $isLate,
                        'device_id' => $request->device_id,
                        'check_in_location' => 'Bulk Check-in'
                    ]
                );
                
                $results[] = [
                    'teacher_id' => $teacherId,
                    'teacher_name' => $teacher->name,
                    'success' => true,
                    'message' => 'Check-in successful' . ($isLate ? ' (Late)' : '')
                ];
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Bulk check-in completed',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk check-in failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportReport(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $teacherId = $request->get('teacher_id');
        $format = $request->get('format', 'excel'); // excel or pdf
        
        $query = BiometricAttendance::with(['teacher'])
            ->whereBetween('date', [$startDate, $endDate]);
            
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        
        $attendances = $query->orderBy('date')->orderBy('teacher_id')->get();
        
        if ($format === 'pdf') {
            // Generate PDF report (would need PDF library)
            return response()->json([
                'success' => true,
                'message' => 'PDF export functionality to be implemented'
            ]);
        } else {
            // Generate Excel report (would need Excel library)
            return response()->json([
                'success' => true,
                'message' => 'Excel export functionality to be implemented',
                'data' => $attendances
            ]);
        }
    }
}