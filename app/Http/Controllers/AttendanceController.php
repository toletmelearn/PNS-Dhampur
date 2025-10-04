<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display attendance overview with filters
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $classId = $request->get('class_id');
        $status = $request->get('status');
        
        $query = Attendance::with(['student.classModel', 'markedBy'])
            ->whereDate('date', $date);
            
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        $attendances = $query->orderBy('created_at', 'desc')->paginate(15);
        $classes = ClassModel::orderBy('name')->get();
        
        // Calculate summary statistics
        $totalStudents = Student::where('status', 'active')->count();
        $todayAttendances = Attendance::whereDate('date', $date)->get();
        $presentCount = $todayAttendances->where('status', 'present')->count();
        $absentCount = $todayAttendances->where('status', 'absent')->count();
        $lateCount = $todayAttendances->where('status', 'late')->count();
        
        $summary = [
            'total_students' => $totalStudents,
            'present' => $presentCount,
            'absent' => $absentCount,
            'late' => $lateCount,
            'attendance_percentage' => $totalStudents > 0 ? round(($presentCount / $totalStudents) * 100, 2) : 0
        ];
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'attendances' => $attendances,
                    'summary' => $summary,
                    'classes' => $classes
                ]
            ]);
        }
        
        return view('attendance.index', compact('attendances', 'classes', 'date', 'summary'));
    }

    /**
     * Show attendance marking interface
     */
    public function markAttendance(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $classId = $request->get('class_id');
        
        $classes = ClassModel::orderBy('name')->get();
        $students = collect();
        $existingAttendances = collect();
        
        if ($classId) {
            $students = Student::with(['classModel', 'user'])
                ->where('class_id', $classId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
                
            $existingAttendances = Attendance::where('class_id', $classId)
                ->whereDate('date', $date)
                ->get()
                ->keyBy('student_id');
        }
        
        return view('attendance.mark', compact('classes', 'students', 'date', 'classId', 'existingAttendances'));
    }

    /**
     * Store attendance records
     */
    public function storeAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'class_id' => 'required|exists:class_models,id',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:present,absent,late'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $date = $request->date;
            $classId = $request->class_id;
            $attendanceData = $request->attendances;
            $markedBy = Auth::id();
            
            $successCount = 0;
            $errors = [];
            
            foreach ($attendanceData as $attendance) {
                try {
                    Attendance::updateOrCreate(
                        [
                            'student_id' => $attendance['student_id'],
                            'date' => $date
                        ],
                        [
                            'class_id' => $classId,
                            'status' => $attendance['status'],
                            'marked_by' => $markedBy
                        ]
                    );
                    $successCount++;
                } catch (\Exception $e) {
                    $student = Student::find($attendance['student_id']);
                    $errors[] = "Failed to mark attendance for " . ($student ? $student->name : 'Student ID: ' . $attendance['student_id']);
                    Log::error('Attendance marking error', [
                        'student_id' => $attendance['student_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $message = "Successfully marked attendance for {$successCount} students.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', $errors);
            }
            
            Log::info('Attendance marked', [
                'date' => $date,
                'class_id' => $classId,
                'success_count' => $successCount,
                'marked_by' => $markedBy
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'data' => [
                        'success_count' => $successCount,
                        'errors' => $errors
                    ]
                ]);
            }
            
            return redirect()->route('attendance.index', ['date' => $date, 'class_id' => $classId])
                ->with('success', $message);
                
        } catch (\Exception $e) {
            Log::error('Attendance storage error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark attendance. Please try again.'
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to mark attendance. Please try again.')
                ->withInput();
        }
    }

    /**
     * Bulk mark attendance for all students in a class
     */
    public function bulkMarkAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'class_id' => 'required|exists:class_models,id',
            'status' => 'required|in:present,absent,late'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $date = $request->date;
            $classId = $request->class_id;
            $status = $request->status;
            $markedBy = Auth::id();
            
            $students = Student::where('class_id', $classId)
                ->where('status', 'active')
                ->get();
                
            $successCount = 0;
            
            foreach ($students as $student) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'date' => $date
                    ],
                    [
                        'class_id' => $classId,
                        'status' => $status,
                        'marked_by' => $markedBy
                    ]
                );
                $successCount++;
            }
            
            Log::info('Bulk attendance marked', [
                'date' => $date,
                'class_id' => $classId,
                'status' => $status,
                'count' => $successCount,
                'marked_by' => $markedBy
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully marked {$successCount} students as {$status}.",
                'data' => [
                    'count' => $successCount
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Bulk attendance error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark bulk attendance. Please try again.'
            ], 500);
        }
    }

    /**
     * Get students by class and date with existing attendance
     */
    public function getStudentsByClassAndDate(Request $request)
    {
        $classId = $request->get('class_id');
        $date = $request->get('date', now()->format('Y-m-d'));
        
        if (!$classId) {
            return response()->json([
                'success' => false,
                'message' => 'Class ID is required'
            ], 400);
        }
        
        try {
            $students = Student::where('class_id', $classId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
                
            $existingAttendances = Attendance::where('class_id', $classId)
                ->whereDate('date', $date)
                ->get()
                ->keyBy('student_id');
                
            $studentsWithAttendance = $students->map(function ($student) use ($existingAttendances) {
                $attendance = $existingAttendances->get($student->id);
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'admission_no' => $student->admission_no,
                    'status' => $attendance ? $attendance->status : null,
                    'marked_at' => $attendance ? $attendance->created_at->format('H:i:s') : null
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $studentsWithAttendance
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get students by class error', [
                'class_id' => $classId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch students'
            ], 500);
        }
    }

    /**
     * Export attendance report
     */
    public function exportReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:class_models,id',
            'format' => 'required|in:pdf,excel,csv'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $classId = $request->class_id;
            $format = $request->format;

            $query = Attendance::with(['student', 'classModel'])
                ->whereBetween('date', [$startDate, $endDate]);

            if ($classId) {
                $query->where('class_id', $classId);
            }

            $attendances = $query->orderBy('date', 'desc')
                ->orderBy('class_id')
                ->get();

            // Generate filename
            $filename = 'attendance_report_' . $startDate . '_to_' . $endDate . '.' . $format;

            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($attendances, $filename);
                case 'excel':
                    return $this->exportToExcel($attendances, $filename);
                case 'pdf':
                    return $this->exportToPdf($attendances, $filename, $startDate, $endDate);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid export format'
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Export attendance report error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export report. Please try again.'
            ], 500);
        }
    }

    /**
     * Get attendance analytics
     */
    public function analytics(Request $request)
    {
        try {
            $classId = $request->get('class_id');
            $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));

            // Overall statistics
            $totalRecords = Attendance::whereBetween('date', [$startDate, $endDate])
                ->when($classId, function ($query) use ($classId) {
                    return $query->where('class_id', $classId);
                })
                ->count();

            $presentCount = Attendance::whereBetween('date', [$startDate, $endDate])
                ->when($classId, function ($query) use ($classId) {
                    return $query->where('class_id', $classId);
                })
                ->where('status', 'present')
                ->count();

            $absentCount = Attendance::whereBetween('date', [$startDate, $endDate])
                ->when($classId, function ($query) use ($classId) {
                    return $query->where('class_id', $classId);
                })
                ->where('status', 'absent')
                ->count();

            $lateCount = Attendance::whereBetween('date', [$startDate, $endDate])
                ->when($classId, function ($query) use ($classId) {
                    return $query->where('class_id', $classId);
                })
                ->where('status', 'late')
                ->count();

            // Daily attendance trends
            $dailyTrends = Attendance::selectRaw('DATE(date) as date, status, COUNT(*) as count')
                ->whereBetween('date', [$startDate, $endDate])
                ->when($classId, function ($query) use ($classId) {
                    return $query->where('class_id', $classId);
                })
                ->groupBy('date', 'status')
                ->orderBy('date')
                ->get()
                ->groupBy('date');

            // Class-wise statistics (if no specific class selected)
            $classStats = [];
            if (!$classId) {
                $classStats = Attendance::selectRaw('class_id, status, COUNT(*) as count')
                    ->with('classModel:id,name')
                    ->whereBetween('date', [$startDate, $endDate])
                    ->groupBy('class_id', 'status')
                    ->get()
                    ->groupBy('class_id');
            }

            // Low attendance students
            $lowAttendanceStudents = Student::with(['attendances' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                }])
                ->when($classId, function ($query) use ($classId) {
                    return $query->where('class_id', $classId);
                })
                ->get()
                ->map(function ($student) {
                    $totalDays = $student->attendances->count();
                    $presentDays = $student->attendances->where('status', 'present')->count();
                    $percentage = $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
                    
                    return [
                        'student' => $student,
                        'total_days' => $totalDays,
                        'present_days' => $presentDays,
                        'percentage' => round($percentage, 2)
                    ];
                })
                ->where('percentage', '<', 75)
                ->sortBy('percentage')
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'overview' => [
                        'total_records' => $totalRecords,
                        'present_count' => $presentCount,
                        'absent_count' => $absentCount,
                        'late_count' => $lateCount,
                        'attendance_percentage' => $totalRecords > 0 ? round(($presentCount / $totalRecords) * 100, 2) : 0
                    ],
                    'daily_trends' => $dailyTrends,
                    'class_stats' => $classStats,
                    'low_attendance_students' => $lowAttendanceStudents
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Attendance analytics error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics data'
            ], 500);
        }
    }

    /**
     * Get monthly attendance report
     */
    public function monthlyReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'class_id' => 'nullable|exists:class_models,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $month = $request->month;
            $year = $request->year;
            $classId = $request->class_id;

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $report = Attendance::getMonthlyAttendanceReport($month, $year, $classId);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Monthly attendance report error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate monthly report'
            ], 500);
        }
    }

    /**
     * Get daily attendance summary
     */
    public function dailySummary(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $classId = $request->get('class_id');

        try {
            $query = Attendance::whereDate('date', $date);
            
            if ($classId) {
                $query->where('class_id', $classId);
            }

            $attendances = $query->with(['student', 'classModel'])->get();

            $summary = [
                'date' => $date,
                'total_students' => $attendances->count(),
                'present' => $attendances->where('status', 'present')->count(),
                'absent' => $attendances->where('status', 'absent')->count(),
                'late' => $attendances->where('status', 'late')->count(),
                'excused' => $attendances->where('status', 'excused')->count(),
                'sick' => $attendances->where('status', 'sick')->count(),
                'attendance_percentage' => $attendances->count() > 0 ? 
                    round(($attendances->where('status', 'present')->count() / $attendances->count()) * 100, 2) : 0
            ];

            // Class-wise breakdown if no specific class selected
            if (!$classId) {
                $classSummary = $attendances->groupBy('class_id')->map(function ($classAttendances) {
                    $class = $classAttendances->first()->classModel;
                    return [
                        'class_name' => $class ? $class->name : 'Unknown',
                        'total' => $classAttendances->count(),
                        'present' => $classAttendances->where('status', 'present')->count(),
                        'absent' => $classAttendances->where('status', 'absent')->count(),
                        'late' => $classAttendances->where('status', 'late')->count(),
                        'percentage' => $classAttendances->count() > 0 ? 
                            round(($classAttendances->where('status', 'present')->count() / $classAttendances->count()) * 100, 2) : 0
                    ];
                });
                $summary['class_breakdown'] = $classSummary;
            }

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            Log::error('Daily attendance summary error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch daily summary'
            ], 500);
        }
    }

    /**
     * Bulk update attendance status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendance_ids' => 'required|array',
            'attendance_ids.*' => 'exists:attendances,id',
            'status' => 'required|in:present,absent,late,excused,sick'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $attendanceIds = $request->attendance_ids;
            $status = $request->status;
            $updatedBy = Auth::id();

            $updated = Attendance::whereIn('id', $attendanceIds)
                ->update([
                    'status' => $status,
                    'marked_by' => $updatedBy,
                    'updated_at' => now()
                ]);

            Log::info('Bulk attendance status updated', [
                'attendance_ids' => $attendanceIds,
                'status' => $status,
                'updated_count' => $updated,
                'updated_by' => $updatedBy
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updated} attendance records to {$status}.",
                'data' => [
                    'updated_count' => $updated
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk update attendance status error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update attendance records'
            ], 500);
        }
    }

    /**
     * Export to CSV
     */
    private function exportToCsv($attendances, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($attendances) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Date', 'Student Name', 'Admission No', 'Class', 'Status', 
                'Check In Time', 'Check Out Time', 'Late Minutes', 'Remarks'
            ]);

            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->date->format('Y-m-d'),
                    $attendance->student->name ?? 'N/A',
                    $attendance->student->admission_no ?? 'N/A',
                    $attendance->classModel->name ?? 'N/A',
                    ucfirst($attendance->status),
                    $attendance->check_in_time ? $attendance->check_in_time->format('H:i:s') : '',
                    $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : '',
                    $attendance->late_minutes ?? 0,
                    $attendance->remarks ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportToPdf($attendances, $filename, $startDate, $endDate)
    {
        // This would require a PDF library like DomPDF or TCPDF
        // For now, return a placeholder response
        return response()->json([
            'success' => false,
            'message' => 'PDF export functionality will be implemented with PDF library'
        ]);
    }

    /**
     * Export to Excel
     */
    private function exportToExcel($attendances, $filename)
    {
        // This would require Laravel Excel package
        // For now, return a placeholder response
        return response()->json([
            'success' => false,
            'message' => 'Excel export functionality will be implemented with Laravel Excel package'
        ]);
    }

    public function show($id)
    {
        return response()->json(Attendance::with('student','class','markedBy')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'=>'required|exists:students,id',
            'class_id'=>'nullable|exists:classes,id',
            'date'=>'required|date',
            'status'=>'required|in:present,absent,late',
            'marked_by'=>'nullable|exists:users,id'
        ]);

        $attendance = Attendance::create($data);
        return response()->json($attendance);
    }

    public function update(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $data = $request->validate([
            'status'=>'sometimes|in:present,absent,late'
        ]);
        $attendance->update($data);
        return response()->json($attendance);
    }

    public function destroy($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();
        return response()->json(['message'=>'Attendance deleted']);
    }
}
