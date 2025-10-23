<?php

namespace App\Http\Controllers;

use App\Models\BiometricAttendance;
use App\Models\Teacher;
use App\Models\AttendanceAnalytics;
use App\Models\AttendanceRegularization;
use App\Services\UserFriendlyErrorService;
use App\Http\Traits\DateRangeValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class BiometricAttendanceController extends Controller
{
    use DateRangeValidationTrait;
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
        // Fix N+1 query by adding eager loading for user relationship
        $teachers = Teacher::with(['user'])->orderBy('name')->get();
        
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

        } catch (Exception $e) {
            Log::error('Biometric check-in error: ' . $e->getTraceAsString());
            return UserFriendlyErrorService::jsonErrorResponse($e, 'biometric_checkin');
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

        } catch (Exception $e) {
            Log::error('Biometric check-out error: ' . $e->getTraceAsString());
            return UserFriendlyErrorService::jsonErrorResponse($e, 'biometric_checkout');
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

        } catch (Exception $e) {
            Log::error('Mark absent error: ' . $e->getTraceAsString());
            return UserFriendlyErrorService::jsonErrorResponse($e, 'attendance_mark');
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

        } catch (Exception $e) {
            Log::error('Get teacher status error: ' . $e->getTraceAsString());
            return UserFriendlyErrorService::jsonErrorResponse($e, 'teacher_fetch');
        }
    }

    public function getDailyReport(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        
        $attendances = BiometricAttendance::with(['teacher'])
            ->whereDate('date', $date)
            ->get();
            
        // Fix N+1 query by adding eager loading for related models
        $allTeachers = Teacher::with(['subjects', 'classes'])->get();
        
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

        } catch (Exception $e) {
            Log::error('Bulk check-in error: ' . $e->getTraceAsString());
            return UserFriendlyErrorService::jsonErrorResponse($e, 'biometric_checkin');
        }
    }

    // Advanced Analytics Methods for Phase 4

    /**
     * Import biometric data from CSV file
     */
    public function importCsvData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'date_format' => 'nullable|string|in:Y-m-d,d/m/Y,m/d/Y',
            'time_format' => 'nullable|string|in:H:i:s,H:i,g:i A'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('csv_file');
            $dateFormat = $request->get('date_format', 'Y-m-d');
            $timeFormat = $request->get('time_format', 'H:i:s');
            
            // Secure CSV file handling
            $filePath = $file->getRealPath();
            $fileHandle = fopen($filePath, 'r');
            
            if (!$fileHandle) {
                throw new Exception('Unable to read CSV file');
            }
            
            $csvData = [];
            while (($row = fgetcsv($fileHandle)) !== false) {
                $csvData[] = $row;
            }
            fclose($fileHandle);
            
            $headers = array_shift($csvData); // Remove header row
            
            $imported = 0;
            $errors = [];
            
            DB::beginTransaction();
            
            foreach ($csvData as $index => $row) {
                try {
                    if (count($row) < 4) continue; // Skip incomplete rows
                    
                    $employeeId = trim($row[0]);
                    $date = Carbon::createFromFormat($dateFormat, trim($row[1]));
                    $checkInTime = $row[2] ? Carbon::createFromFormat($timeFormat, trim($row[2])) : null;
                    $checkOutTime = $row[3] ? Carbon::createFromFormat($timeFormat, trim($row[3])) : null;
                    
                    // Find teacher by employee ID
                    $teacher = Teacher::whereHas('user', function ($query) use ($employeeId) {
            $query->where('employee_id', $employeeId);
        })->first();
                    if (!$teacher) {
                        $errors[] = "Row " . ($index + 2) . ": Teacher not found for employee ID: $employeeId";
                        continue;
                    }
                    
                    // Use configurable thresholds for attendance calculations
                    $schoolStartTime = Carbon::createFromFormat('H:i:s', config('attendance.school_start_time', '08:00:00'));
                    $schoolEndTime = Carbon::createFromFormat('H:i:s', config('attendance.school_end_time', '16:00:00'));
                    $graceMinutes = (int) config('attendance.grace_minutes', 15);
                    $minimumWorkingHours = (float) config('attendance.minimum_working_hours', 8);
                    
                    $checkInTimeOnly = $checkInTime ? Carbon::createFromFormat('H:i:s', $checkInTime->format('H:i:s')) : null;
                    $checkOutTimeOnly = $checkOutTime ? Carbon::createFromFormat('H:i:s', $checkOutTime->format('H:i:s')) : null;
                    
                    $isLate = $checkInTimeOnly ? $checkInTimeOnly->gt($schoolStartTime->copy()->addMinutes($graceMinutes)) : false;
                    $workingMinutes = ($checkInTime && $checkOutTime) ? $checkInTime->diffInMinutes($checkOutTime) : 0;
                    $workingHours = round($workingMinutes / 60, 2);
                    $isEarlyDeparture = $checkOutTimeOnly ? ($checkOutTimeOnly->lt($schoolEndTime) || ($workingHours < $minimumWorkingHours)) : false;
                    
                    // Create or update attendance record
                    $attendance = BiometricAttendance::updateOrCreate(
                        [
                            'teacher_id' => $teacher->id,
                            'date' => $date->format('Y-m-d')
                        ],
                        [
                            'check_in_time' => $checkInTime,
                            'check_out_time' => $checkOutTime,
                            'status' => $checkInTime ? 'present' : 'absent',
                            'is_late' => $isLate,
                            'is_early_departure' => $isEarlyDeparture,
                            'working_hours' => $workingHours,
                            'biometric_data' => json_encode([
                                'import_source' => 'csv',
                                'import_date' => now(),
                                'original_data' => $row
                            ])
                        ]
                    );
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Successfully imported $imported records",
                'data' => [
                    'imported_count' => $imported,
                    'error_count' => count($errors),
                    'errors' => $errors
                ]
            ]);
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('CSV import error: ' . $e->getTraceAsString());
            return UserFriendlyErrorService::jsonErrorResponse($e, 'import_data');
        }
    }

    /**
     * Get advanced analytics dashboard data
     */
    public function getAnalyticsDashboard(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $teacherId = $request->get('teacher_id');
        
        try {
            // Get or calculate analytics for the month
            $analytics = AttendanceAnalytics::getMonthlyAnalytics($month, $teacherId);
            
            // Get leave patterns
            $leavePatterns = AttendanceAnalytics::getLeavePatterns($month, $teacherId);
            
            // Get performance metrics
            $performanceMetrics = AttendanceAnalytics::getPerformanceMetrics($month, $teacherId);
            
            // Get top performers
            $topPerformers = AttendanceAnalytics::getTopPerformers($month, 10);
            
            // Get dashboard summary
            $dashboardSummary = AttendanceAnalytics::getDashboardSummary($month);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'analytics' => $analytics,
                    'leave_patterns' => $leavePatterns,
                    'performance_metrics' => $performanceMetrics,
                    'top_performers' => $topPerformers,
                    'dashboard_summary' => $dashboardSummary,
                    'month' => $month
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Analytics dashboard error: ' . $e->getTraceAsString());
            return UserFriendlyErrorService::jsonErrorResponse($e, 'report_generate');
        }
    }

    /**
     * Calculate and update analytics for all teachers
     */
    public function calculateMonthlyAnalytics(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        
        try {
            $result = AttendanceAnalytics::calculateBulkAnalytics($month);
            
            return response()->json([
                'success' => true,
                'message' => 'Analytics calculated successfully',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            Log::error('Analytics calculation error: ' . $e->getTraceAsString());
            return UserFriendlyErrorService::jsonErrorResponse($e, 'report_generate');
        }
    }

    /**
     * Get attendance regularization requests
     */
    public function getRegularizationRequests(Request $request)
    {
        $status = $request->get('status', 'all');
        $teacherId = $request->get('teacher_id');
        
        $query = AttendanceRegularization::with(['teacher', 'biometricAttendance', 'reviewedBy'])
            ->orderBy('created_at', 'desc');
            
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        
        $requests = $query->paginate(20);
        
        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Create attendance regularization request
     */
    public function createRegularizationRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'teacher_id' => 'required|exists:teachers,id',
            'attendance_date' => 'required|date',
            'request_type' => 'required|in:check_in,check_out,both,absent_to_present',
            'requested_check_in' => 'nullable|date_format:H:i:s',
            'requested_check_out' => 'nullable|date_format:H:i:s',
            'reason' => 'required|string|max:500',
            'supporting_documents' => 'nullable|array',
            'supporting_documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $documents = [];
            if ($request->hasFile('supporting_documents')) {
                foreach ($request->file('supporting_documents') as $file) {
                    // Generate secure filename to prevent directory traversal and overwrite attacks
                    $extension = strtolower($file->getClientOriginalExtension());
                    $secureFilename = 'regularization_' . $request->teacher_id . '_' . time() . '_' . Str::random(10) . '.' . $extension;
                    
                    // Store with secure filename
                    $path = $file->storeAs('regularization_documents', $secureFilename, 'public');
                    $documents[] = [
                        'filename' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                        'uploaded_at' => now()
                    ];
                }
            }
            
            $regularization = AttendanceRegularization::createRequest(
                $request->teacher_id,
                $request->attendance_date,
                $request->request_type,
                $request->reason,
                $request->requested_check_in,
                $request->requested_check_out,
                $documents
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Regularization request created successfully',
                'data' => $regularization->load(['teacher', 'biometricAttendance'])
            ]);
            
        } catch (Exception $e) {
            Log::error('Regularization request error: ' . $e->getTraceAsString());
            return UserFriendlyErrorService::jsonErrorResponse($e, 'general');
        }
    }

    /**
     * Approve or reject regularization request
     */
    public function processRegularizationRequest(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:approve,reject',
            'admin_remarks' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $regularization = AttendanceRegularization::findOrFail($id);
            
            if ($request->action === 'approve') {
                $result = $regularization->approve(auth()->id(), $request->admin_remarks);
            } else {
                $result = $regularization->reject(auth()->id(), $request->admin_remarks);
            }
            
            return response()->json([
                'success' => true,
                'message' => "Regularization request {$request->action}d successfully",
                'data' => $regularization->fresh()->load(['teacher', 'biometricAttendance', 'reviewedBy'])
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process regularization request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed attendance report with analytics
     */
    public function getDetailedReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            ...$this->getFilterDateRangeValidationRules(),
            'teacher_id' => 'nullable|exists:teachers,id',
            'include_analytics' => 'nullable|boolean'
        ], $this->getDateRangeValidationMessages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $startDate = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('date_to', now()->format('Y-m-d'));
        $teacherId = $request->get('teacher_id');
        $includeAnalytics = $request->get('include_analytics', true);
        
        try {
            $query = BiometricAttendance::with(['teacher'])
                ->whereBetween('date', [$startDate, $endDate]);
                
            if ($teacherId) {
                $query->where('teacher_id', $teacherId);
            }
            
            $attendances = $query->orderBy('date')->orderBy('teacher_id')->get();
            
            $report = [
                'period' => [
                    'date_from' => $startDate,
                    'date_to' => $endDate
                ],
                'attendances' => $attendances,
                'summary' => [
                    'total_records' => $attendances->count(),
                    'present_days' => $attendances->where('status', 'present')->count(),
                    'absent_days' => $attendances->where('status', 'absent')->count(),
                    'late_arrivals' => $attendances->where('is_late', true)->count(),
                    'early_departures' => $attendances->where('is_early_departure', true)->count(),
                    'total_working_hours' => $attendances->sum('working_hours'),
                    'average_working_hours' => $attendances->where('working_hours', '>', 0)->avg('working_hours')
                ]
            ];
            
            if ($includeAnalytics && $teacherId) {
                // Get analytics for the teacher
                $monthStart = Carbon::parse($startDate)->startOfMonth();
                $monthEnd = Carbon::parse($endDate)->endOfMonth();
                
                $analytics = AttendanceAnalytics::where('teacher_id', $teacherId)
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->get();
                    
                $report['analytics'] = $analytics;
            }
            
            return response()->json([
                'success' => true,
                'data' => $report
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate detailed report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leave pattern analysis
     */
    public function getLeavePatternAnalysis(Request $request)
    {
        $teacherId = $request->get('teacher_id');
        $months = $request->get('months', 6); // Last 6 months by default
        
        try {
            $endDate = now();
            $startDate = now()->subMonths($months);
            
            $query = BiometricAttendance::where('status', 'absent')
                ->whereBetween('date', [$startDate, $endDate]);
                
            if ($teacherId) {
                $query->where('teacher_id', $teacherId);
            }
            
            $absences = $query->with('teacher')->get();
            
            // Analyze patterns
            $patterns = [
                'by_day_of_week' => [],
                'by_month' => [],
                'consecutive_absences' => [],
                'frequent_absentees' => []
            ];
            
            // Day of week pattern
            foreach ($absences as $absence) {
                $dayOfWeek = Carbon::parse($absence->date)->format('l');
                $patterns['by_day_of_week'][$dayOfWeek] = ($patterns['by_day_of_week'][$dayOfWeek] ?? 0) + 1;
            }
            
            // Monthly pattern
            foreach ($absences as $absence) {
                $month = Carbon::parse($absence->date)->format('Y-m');
                $patterns['by_month'][$month] = ($patterns['by_month'][$month] ?? 0) + 1;
            }
            
            // Frequent absentees (if not filtering by teacher)
            if (!$teacherId) {
                $absenteeCount = $absences->groupBy('teacher_id')->map->count()->sortDesc();
                $topAbsenteeIds = $absenteeCount->take(10)->keys()->toArray();
                $teachers = Teacher::whereIn('id', $topAbsenteeIds)->get()->keyBy('id');
                
                foreach ($absenteeCount->take(10) as $tId => $count) {
                    $teacher = $teachers->get($tId);
                    $patterns['frequent_absentees'][] = [
                        'teacher' => $teacher,
                        'absence_count' => $count,
                        'absence_rate' => round(($count / $months) * 100 / 30, 2) // Approximate monthly rate
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'patterns' => $patterns,
                    'period' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'months' => $months
                    ],
                    'total_absences' => $absences->count()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to analyze leave patterns: ' . $e->getMessage()
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

    public function regularizationStatistics(Request $request)
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to = $request->get('to', now()->toDateString());
        $teacherId = $request->get('teacher_id');
    
        $query = \App\Models\AttendanceRegularization::query();
        $query->whereBetween('attendance_date', [$from, $to]);
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
    
        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', 'pending')->count();
        $approved = (clone $query)->where('status', 'approved')->count();
        $rejected = (clone $query)->where('status', 'rejected')->count();
    
        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'approval_rate' => $total ? round(($approved / $total) * 100, 2) : 0,
                'pending_rate' => $total ? round(($pending / $total) * 100, 2) : 0,
                'rejection_rate' => $total ? round(($rejected / $total) * 100, 2) : 0,
            ],
        ]);
    }

    public function existingAttendance(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|integer',
            'date' => 'required|date',
        ]);
    
        $attendance = \App\Models\BiometricAttendance::where('teacher_id', $request->teacher_id)
            ->whereDate('date', $request->date)
            ->first();
    
        return response()->json([
            'success' => true,
            'data' => $attendance,
        ]);
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
    
        $count = \App\Models\AttendanceRegularization::whereIn('id', $request->ids)
            ->update(['status' => 'approved', 'admin_remarks' => $request->get('admin_remarks')]);
    
        return response()->json([
            'success' => true,
            'message' => "Bulk approved {$count} requests",
            'data' => ['approved_count' => $count],
        ]);
    }

    public function exportRegularization(Request $request)
    {
        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to = $request->get('to', now()->toDateString());
        $teacherId = $request->get('teacher_id');
    
        $query = \App\Models\AttendanceRegularization::with(['teacher'])
            ->whereBetween('attendance_date', [$from, $to]);
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        $rows = $query->orderBy('attendance_date', 'desc')->get();
    
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="regularization_export.csv"',
        ];
    
        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Teacher', 'Employee ID', 'Date', 'Type', 'Requested In', 'Requested Out', 'Status', 'Reason', 'Created At']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    optional($r->teacher)->name,
                    optional($r->teacher)->employee_id,
                    $r->attendance_date,
                    $r->request_type,
                    $r->requested_check_in,
                    $r->requested_check_out,
                    $r->status,
                    $r->reason,
                    optional($r->created_at)->toDateTimeString(),
                ]);
            }
            fclose($handle);
        };
    
        return response()->stream($callback, 200, $headers);
    }

    public function exportAnalytics(Request $request)
    {
        $month = $request->get('month', now()->format('Y-m'));
        $teacherId = $request->get('teacher_id');
    
        $analytics = \App\Models\AttendanceAnalytics::getMonthlyAnalytics($month, $teacherId);
    
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_analytics_'.str_replace(':', '-', $month).'.csv"',
        ];
    
        $callback = function () use ($analytics) {
            $handle = fopen('php://output', 'w');
            // Header
            fputcsv($handle, [
                'Teacher', 'Employee ID', 'Department', 'Present Days', 'Absent Days',
                'Attendance %', 'Punctuality Score', 'Late Arrivals', 'Early Departures',
                'Average Working Hours', 'Grade'
            ]);
            foreach ($analytics as $item) {
                fputcsv($handle, [
                    data_get($item, 'teacher.name'),
                    data_get($item, 'teacher.employee_id'),
                    data_get($item, 'teacher.department'),
                    data_get($item, 'present_days'),
                    data_get($item, 'absent_days'),
                    data_get($item, 'attendance_percentage'),
                    data_get($item, 'punctuality_score'),
                    data_get($item, 'late_arrivals'),
                    data_get($item, 'early_departures'),
                    data_get($item, 'average_working_hours'),
                    data_get($item, 'attendance_grade'),
                ]);
            }
            fclose($handle);
        };
    
        return response()->stream($callback, 200, $headers);
    }
}