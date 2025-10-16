<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Attendance\Requests\StoreAttendanceRequest;
use App\Modules\Attendance\Requests\UpdateAttendanceRequest;
use App\Modules\Attendance\Resources\AttendanceResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
        
        // Apply middleware
        $this->middleware(['auth', 'verified']);
        $this->middleware('role:admin,principal,teacher')->except(['show', 'myAttendance']);
        $this->middleware('role:admin,principal,teacher,student,parent')->only(['show', 'myAttendance']);
        
        // Rate limiting for sensitive operations
        $this->middleware('throttle:60,1')->only(['store', 'update', 'bulkAction', 'markAttendance']);
    }

    /**
     * Display attendance dashboard
     */
    public function index(Request $request): View|JsonResponse
    {
        try {
            $filters = $request->only([
                'date', 'class_id', 'section', 'subject_id', 'teacher_id', 
                'status', 'start_date', 'end_date'
            ]);

            $attendanceData = $this->attendanceService->getAttendanceDashboard($filters);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $attendanceData
                ]);
            }

            return view('modules.attendance.index', compact('attendanceData', 'filters'));
        } catch (\Exception $e) {
            Log::error('Error fetching attendance dashboard: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching attendance data'], 500);
            }
            
            return redirect()->back()->with('error', 'Error fetching attendance data');
        }
    }

    /**
     * Show attendance marking form
     */
    public function create(Request $request): View
    {
        $classes = $this->attendanceService->getClasses();
        $subjects = $this->attendanceService->getSubjects();
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $classId = $request->get('class_id');
        $section = $request->get('section');
        $subjectId = $request->get('subject_id');
        
        $students = null;
        $existingAttendance = null;
        
        if ($classId && $section) {
            $students = $this->attendanceService->getStudentsByClass($classId, $section);
            $existingAttendance = $this->attendanceService->getExistingAttendance($date, $classId, $section, $subjectId);
        }
        
        return view('modules.attendance.create', compact(
            'classes', 'subjects', 'date', 'classId', 'section', 
            'subjectId', 'students', 'existingAttendance'
        ));
    }

    /**
     * Store attendance records
     */
    public function store(StoreAttendanceRequest $request): RedirectResponse|JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $attendance = $this->attendanceService->markAttendance($request->validated());
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attendance marked successfully',
                    'data' => AttendanceResource::collection($attendance)
                ], 201);
            }
            
            return redirect()->route('attendance.index')
                           ->with('success', 'Attendance marked successfully');
                           
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking attendance: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error marking attendance'], 500);
            }
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error marking attendance');
        }
    }

    /**
     * Display specific attendance record or student attendance
     */
    public function show(Request $request, $id): View|JsonResponse
    {
        try {
            // Check if ID is for a specific attendance record or student
            $isStudentId = $request->has('student_id') || $request->has('type') && $request->type === 'student';
            
            if ($isStudentId) {
                $attendanceData = $this->attendanceService->getStudentAttendance($id, $request->only(['start_date', 'end_date', 'subject_id']));
            } else {
                $attendanceData = $this->attendanceService->getAttendanceById($id);
            }
            
            if (!$attendanceData) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Attendance record not found'], 404);
                }
                
                return redirect()->route('attendance.index')->with('error', 'Attendance record not found');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $isStudentId ? $attendanceData : new AttendanceResource($attendanceData)
                ]);
            }

            $viewName = $isStudentId ? 'modules.attendance.student-attendance' : 'modules.attendance.show';
            return view($viewName, compact('attendanceData'));
        } catch (\Exception $e) {
            Log::error('Error fetching attendance: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching attendance'], 500);
            }
            
            return redirect()->route('attendance.index')->with('error', 'Error fetching attendance');
        }
    }

    /**
     * Show form for editing attendance
     */
    public function edit($id): View
    {
        $attendance = $this->attendanceService->getAttendanceById($id);
        
        if (!$attendance) {
            return redirect()->route('attendance.index')->with('error', 'Attendance record not found');
        }
        
        return view('modules.attendance.edit', compact('attendance'));
    }

    /**
     * Update attendance record
     */
    public function update(UpdateAttendanceRequest $request, $id): RedirectResponse|JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $attendance = $this->attendanceService->updateAttendance($id, $request->validated());
            
            if (!$attendance) {
                DB::rollBack();
                
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Attendance record not found'], 404);
                }
                
                return redirect()->route('attendance.index')->with('error', 'Attendance record not found');
            }
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attendance updated successfully',
                    'data' => new AttendanceResource($attendance)
                ]);
            }
            
            return redirect()->route('attendance.show', $attendance->id)
                           ->with('success', 'Attendance updated successfully');
                           
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating attendance: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error updating attendance'], 500);
            }
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error updating attendance');
        }
    }

    /**
     * Delete attendance record
     */
    public function destroy(Request $request, $id): RedirectResponse|JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $deleted = $this->attendanceService->deleteAttendance($id);
            
            if (!$deleted) {
                DB::rollBack();
                
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Attendance record not found'], 404);
                }
                
                return redirect()->route('attendance.index')->with('error', 'Attendance record not found');
            }
            
            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attendance record deleted successfully'
                ]);
            }
            
            return redirect()->route('attendance.index')
                           ->with('success', 'Attendance record deleted successfully');
                           
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting attendance: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error deleting attendance'], 500);
            }
            
            return redirect()->back()->with('error', 'Error deleting attendance');
        }
    }

    /**
     * Quick attendance marking for teachers
     */
    public function markAttendance(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'class_id' => 'required|exists:classes,id',
            'section' => 'required|string',
            'subject_id' => 'nullable|exists:subjects,id',
            'period' => 'nullable|integer|min:1|max:8',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status' => 'required|in:present,absent,late,excused'
        ]);

        try {
            DB::beginTransaction();
            
            $result = $this->attendanceService->quickMarkAttendance($request->all());
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance marked successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking quick attendance: ' . $e->getMessage());
            
            return response()->json(['success' => false, 'message' => 'Error marking attendance'], 500);
        }
    }

    /**
     * Get attendance report
     */
    public function report(Request $request): View|JsonResponse
    {
        $request->validate([
            'type' => 'required|in:daily,weekly,monthly,custom,student,class',
            'start_date' => 'required_if:type,custom|date',
            'end_date' => 'required_if:type,custom|date|after_or_equal:start_date',
            'class_id' => 'nullable|exists:classes,id',
            'section' => 'nullable|string',
            'student_id' => 'required_if:type,student|exists:students,id',
            'subject_id' => 'nullable|exists:subjects,id'
        ]);

        try {
            $reportData = $this->attendanceService->generateReport($request->all());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $reportData
                ]);
            }

            return view('modules.attendance.report', compact('reportData'));
        } catch (\Exception $e) {
            Log::error('Error generating attendance report: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error generating report'], 500);
            }
            
            return redirect()->back()->with('error', 'Error generating report');
        }
    }

    /**
     * Get class attendance for a specific date
     */
    public function classAttendance(Request $request): View|JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'class_id' => 'required|exists:classes,id',
            'section' => 'required|string',
            'subject_id' => 'nullable|exists:subjects,id'
        ]);

        try {
            $attendanceData = $this->attendanceService->getClassAttendance($request->all());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $attendanceData
                ]);
            }

            return view('modules.attendance.class-attendance', compact('attendanceData'));
        } catch (\Exception $e) {
            Log::error('Error fetching class attendance: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching class attendance'], 500);
            }
            
            return redirect()->back()->with('error', 'Error fetching class attendance');
        }
    }

    /**
     * Get my attendance (for students)
     */
    public function myAttendance(Request $request): View|JsonResponse
    {
        try {
            $user = Auth::user();
            $studentId = null;
            
            if ($user->hasRole('student')) {
                $studentId = $user->student->id ?? null;
            } elseif ($user->hasRole('parent')) {
                // Parent can view their children's attendance
                $studentId = $request->get('student_id');
                if (!$this->attendanceService->canParentViewStudent($user->id, $studentId)) {
                    if ($request->expectsJson()) {
                        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                    }
                    return redirect()->back()->with('error', 'Unauthorized access');
                }
            }
            
            if (!$studentId) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Student not found'], 404);
                }
                return redirect()->back()->with('error', 'Student not found');
            }

            $filters = $request->only(['start_date', 'end_date', 'subject_id']);
            $attendanceData = $this->attendanceService->getStudentAttendance($studentId, $filters);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $attendanceData
                ]);
            }

            return view('modules.attendance.my-attendance', compact('attendanceData'));
        } catch (\Exception $e) {
            Log::error('Error fetching my attendance: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Error fetching attendance'], 500);
            }
            
            return redirect()->back()->with('error', 'Error fetching attendance');
        }
    }

    /**
     * Handle bulk attendance operations
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:mark_present,mark_absent,mark_late,delete',
            'attendance_ids' => 'required|array|min:1',
            'attendance_ids.*' => 'exists:attendance,id',
            'date' => 'required_if:action,mark_present,mark_absent,mark_late|date',
            'reason' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();
            
            $result = $this->attendanceService->bulkAction($request->all());
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'] ?? null
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error performing bulk action: ' . $e->getMessage());
            
            return response()->json(['success' => false, 'message' => 'Error performing bulk action'], 500);
        }
    }

    /**
     * Export attendance data
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:csv,excel,pdf',
            'type' => 'required|in:daily,weekly,monthly,custom,student,class',
            'start_date' => 'required_if:type,custom|date',
            'end_date' => 'required_if:type,custom|date',
            'class_id' => 'nullable|exists:classes,id',
            'section' => 'nullable|string',
            'student_id' => 'nullable|exists:students,id'
        ]);

        try {
            $result = $this->attendanceService->exportAttendance($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Export completed successfully',
                'download_url' => $result['download_url']
            ]);
        } catch (\Exception $e) {
            Log::error('Error exporting attendance: ' . $e->getMessage());
            
            return response()->json(['success' => false, 'message' => 'Error exporting data'], 500);
        }
    }

    /**
     * Get attendance statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:today,week,month,year',
            'class_id' => 'nullable|exists:classes,id',
            'section' => 'nullable|string'
        ]);

        try {
            $stats = $this->attendanceService->getStatistics($request->all());
            
            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching attendance statistics: ' . $e->getMessage());
            
            return response()->json(['success' => false, 'message' => 'Error fetching statistics'], 500);
        }
    }

    /**
     * Get attendance trends
     */
    public function trends(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|in:week,month,quarter,year',
            'class_id' => 'nullable|exists:classes,id',
            'section' => 'nullable|string',
            'student_id' => 'nullable|exists:students,id'
        ]);

        try {
            $trends = $this->attendanceService->getAttendanceTrends($request->all());
            
            return response()->json([
                'success' => true,
                'data' => $trends
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching attendance trends: ' . $e->getMessage());
            
            return response()->json(['success' => false, 'message' => 'Error fetching trends'], 500);
        }
    }

    /**
     * Send attendance notifications
     */
    public function sendNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:daily_summary,absent_alert,low_attendance_warning',
            'date' => 'required|date',
            'class_id' => 'nullable|exists:classes,id',
            'section' => 'nullable|string',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id'
        ]);

        try {
            $result = $this->attendanceService->sendNotifications($request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Notifications sent successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending attendance notifications: ' . $e->getMessage());
            
            return response()->json(['success' => false, 'message' => 'Error sending notifications'], 500);
        }
    }
}