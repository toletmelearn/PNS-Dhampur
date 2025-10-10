<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\ClassModel;
use App\Services\UserFriendlyErrorService;
use App\Traits\HandlesApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Traits\DateRangeValidationTrait;

class AttendanceController extends Controller
{
    use HandlesApiResponses, DateRangeValidationTrait;
    /**
     * Display attendance overview with filters
     */
    public function index(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $classId = $request->get('class_id');
        $status = $request->get('status');
        
        $query = Attendance::with([
            'student:id,name,admission_no,class_id',
            'student.classModel:id,name,section',
            'markedBy:id,name'
        ])->whereDate('date', $date);
            
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['created_at', 'status', 'student_id'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $attendances = $query->paginate(15);
        $classes = ClassModel::select('id', 'name', 'section')->orderBy('name')->get();
        
        // Calculate summary statistics efficiently in a single query
        $summaryQuery = Attendance::selectRaw('
            COUNT(*) as total_records,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count
        ')->whereDate('date', $date);
        
        if ($classId) {
            $summaryQuery->where('class_id', $classId);
        }
        
        $summaryData = $summaryQuery->first();
        $totalStudents = Student::where('status', 'active')
            ->when($classId, function($query) use ($classId) {
                return $query->where('class_id', $classId);
            })
            ->count();
        
        $summary = [
            'total_students' => $totalStudents,
            'present' => $summaryData->present_count ?? 0,
            'absent' => $summaryData->absent_count ?? 0,
            'late' => $summaryData->late_count ?? 0,
            'attendance_percentage' => $totalStudents > 0 ? 
                round((($summaryData->present_count ?? 0) / $totalStudents) * 100, 2) : 0
        ];
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'attendances' => $attendances,
                    'summary' => $summary,
                    'classes' => $classes,
                    'pagination' => [
                        'current_page' => $attendances->currentPage(),
                        'last_page' => $attendances->lastPage(),
                        'per_page' => $attendances->perPage(),
                        'total' => $attendances->total(),
                        'from' => $attendances->firstItem(),
                        'to' => $attendances->lastItem(),
                    ]
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
        
        $classes = ClassModel::select('id', 'name', 'section')->orderBy('name')->get();
        $students = collect();
        $existingAttendances = collect();
        
        if ($classId) {
            $students = Student::with(['classModel:id,name,section', 'user:id,name,email'])
                ->select('id', 'name', 'admission_no', 'class_id', 'user_id', 'status')
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

        return DB::transaction(function () use ($request) {
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
                    // Re-throw to trigger transaction rollback
                    throw new \Exception("Failed to mark attendance for student ID: " . $attendance['student_id'] . ". " . $e->getMessage());
                }
            }
            
            if ($successCount === 0) {
                throw new \Exception('No attendance records were successfully created');
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
            
            return [
                'success' => true,
                'message' => $message,
                'data' => [
                    'success_count' => $successCount,
                    'errors' => $errors
                ],
                'redirect' => route('attendance.index', ['date' => $date, 'class_id' => $classId])
            ];
        });
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
            return $this->jsonValidationErrorResponse($validator);
        }

        return DB::transaction(function () use ($request) {
            $date = $request->date;
            $classId = $request->class_id;
            $status = $request->status;
            $markedBy = Auth::id();
            
            $students = Student::where('class_id', $classId)
                ->where('status', 'active')
                ->get();

            if ($students->isEmpty()) {
                throw new \Exception('No active students found in the selected class.');
            }
                
            $successCount = 0;
            $errors = [];
            
            foreach ($students as $student) {
                try {
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
                } catch (\Exception $e) {
                    $errors[] = "Failed to mark attendance for {$student->name}";
                    Log::error('Bulk attendance marking error', [
                        'student_id' => $student->id,
                        'error' => $e->getMessage()
                    ]);
                    // Re-throw to trigger transaction rollback
                    throw new \Exception("Failed to mark attendance for student {$student->name}. " . $e->getMessage());
                }
            }
            
            if ($successCount === 0) {
                throw new \Exception('No attendance records were successfully created');
            }
            
            Log::info('Bulk attendance marked', [
                'date' => $date,
                'class_id' => $classId,
                'status' => $status,
                'count' => $successCount,
                'marked_by' => $markedBy
            ]);
            
            return [
                'success' => true,
                'message' => "Successfully marked {$successCount} students as {$status}.",
                'data' => [
                    'count' => $successCount
                ]
            ];
        });
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
            
            return UserFriendlyErrorService::jsonErrorResponse($e, 'student_fetch');
        }
    }

    /**
     * Export attendance report
     */
    public function exportReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            ...$this->getFilterDateRangeValidationRules(),
            'class_id' => 'nullable|exists:class_models,id',
            'format' => 'required|in:pdf,excel,csv'
        ], $this->getDateRangeValidationMessages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDate = $request->date_from;
            $endDate = $request->date_to;
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
            Log::error('Export report error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return UserFriendlyErrorService::jsonErrorResponse($e, 'export_data');
        }
    }

    /**
     * Get attendance analytics
     */
    public function analytics(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $classId = $request->get('class_id');
        
        // Overall statistics with efficient single query
        $overallStats = Attendance::selectRaw('
            COUNT(*) as total_records,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count
        ')
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->when($classId, function($query) use ($classId) {
            return $query->where('class_id', $classId);
        })
        ->first();
        
        $totalStudents = Student::where('status', 'active')
            ->when($classId, function($query) use ($classId) {
                return $query->where('class_id', $classId);
            })
            ->count();
        
        // Daily attendance trends with optimized query
        $dailyTrends = Attendance::selectRaw('
            DATE(date) as attendance_date,
            COUNT(*) as total_records,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count
        ')
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->when($classId, function($query) use ($classId) {
            return $query->where('class_id', $classId);
        })
        ->groupBy('attendance_date')
        ->orderBy('attendance_date')
        ->get()
        ->map(function($item) use ($totalStudents) {
            $item->attendance_percentage = $totalStudents > 0 ? 
                round(($item->present_count / $totalStudents) * 100, 2) : 0;
            return $item;
        });
        
        // Class-wise statistics with optimized query
        $classStats = Attendance::selectRaw('
            class_id,
            COUNT(*) as total_records,
            SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count
        ')
        ->with(['classModel:id,name,section'])
        ->whereBetween('date', [$dateFrom, $dateTo])
        ->when($classId, function($query) use ($classId) {
            return $query->where('class_id', $classId);
        })
        ->groupBy('class_id')
        ->get()
        ->map(function($item) {
            $classStudents = Student::where('class_id', $item->class_id)
                ->where('status', 'active')
                ->count();
            $item->attendance_percentage = $classStudents > 0 ? 
                round(($item->present_count / $classStudents) * 100, 2) : 0;
            $item->total_students = $classStudents;
            return $item;
        });
        
        // Low attendance students with optimized query
        $lowAttendanceStudents = Student::select('id', 'name', 'admission_no', 'class_id')
            ->with(['classModel:id,name,section'])
            ->withCount([
                'attendances as total_attendance' => function($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('date', [$dateFrom, $dateTo]);
                },
                'attendances as present_count' => function($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('date', [$dateFrom, $dateTo])
                          ->where('status', 'present');
                }
            ])
            ->having('total_attendance', '>', 0)
            ->get()
            ->map(function($student) {
                $student->attendance_percentage = $student->total_attendance > 0 ? 
                    round(($student->present_count / $student->total_attendance) * 100, 2) : 0;
                return $student;
            })
            ->filter(function($student) {
                return $student->attendance_percentage < 75;
            })
            ->sortBy('attendance_percentage')
            ->take(10);
        
        $classes = ClassModel::select('id', 'name', 'section')->orderBy('name')->get();
        
        $analytics = [
            'overall_stats' => [
                'total_students' => $totalStudents,
                'present' => $overallStats->present_count ?? 0,
                'absent' => $overallStats->absent_count ?? 0,
                'late' => $overallStats->late_count ?? 0,
                'attendance_percentage' => $totalStudents > 0 ? 
                    round((($overallStats->present_count ?? 0) / $totalStudents) * 100, 2) : 0
            ],
            'daily_trends' => $dailyTrends,
            'class_stats' => $classStats,
            'low_attendance_students' => $lowAttendanceStudents
        ];
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
        }
        
        return view('attendance.analytics', compact('analytics', 'classes', 'dateFrom', 'dateTo'));
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

            return UserFriendlyErrorService::jsonErrorResponse($e, 'report_generate');
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

            return UserFriendlyErrorService::jsonErrorResponse($e, 'report_generate');
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

        return DB::transaction(function () use ($request) {
            try {
                $attendanceIds = $request->attendance_ids;
                $status = $request->status;
                $updatedBy = Auth::id();

                // Verify all attendance records exist and can be updated
                $existingRecords = Attendance::whereIn('id', $attendanceIds)->count();
                if ($existingRecords !== count($attendanceIds)) {
                    throw new \Exception('Some attendance records not found or cannot be updated');
                }

                $updated = Attendance::whereIn('id', $attendanceIds)
                    ->update([
                        'status' => $status,
                        'marked_by' => $updatedBy,
                        'updated_at' => now()
                    ]);

                if ($updated === 0) {
                    throw new \Exception('No attendance records were updated');
                }

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
                    'trace' => $e->getTraceAsString(),
                    'request_data' => $request->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update attendance records: ' . $e->getMessage()
                ], 500);
            }
        });
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
