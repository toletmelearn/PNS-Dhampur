<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherAbsence;
use App\Models\TeacherSubstitution;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Subject;
use App\Services\SubstituteNotificationService;
use App\Http\Traits\DateRangeValidationTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubstitutionController extends Controller
{
    use DateRangeValidationTrait;
    
    protected $notificationService;

    public function __construct(SubstituteNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display the substitution dashboard
     */
    public function index()
    {
        $stats = TeacherSubstitution::getDashboardStats();
        $todaySubstitutions = TeacherSubstitution::getTodaySubstitutions();
        $emergencySubstitutions = TeacherSubstitution::getEmergencySubstitutions();
        $upcomingSubstitutions = TeacherSubstitution::getUpcomingSubstitutions(null, 7);
        
        // Pass classes and teachers data to avoid N+1 queries in modals
        $classes = ClassModel::with(['students'])->get();
        $teachers = Teacher::with(['user'])->where('is_active', true)->get();

        return view('substitution.dashboard', compact(
            'stats',
            'todaySubstitutions',
            'emergencySubstitutions',
            'upcomingSubstitutions',
            'classes',
            'teachers'
        ));
    }

    /**
     * Show all substitutions with filtering
     */
    public function substitutions(Request $request)
    {
        // Validate date range parameters
        $request->validate(array_merge(
            $this->getFilterDateRangeValidationRules(),
            [
                'status' => 'nullable|string|in:pending,assigned,confirmed,completed,cancelled',
                'teacher_id' => 'nullable|exists:teachers,id',
                'class_id' => 'nullable|exists:classes,id',
                'emergency' => 'nullable|boolean',
            ]
        ), $this->getDateRangeValidationMessages());

        $query = TeacherSubstitution::with(['originalTeacher', 'substituteTeacher', 'class', 'subject', 'absence']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('substitution_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('substitution_date', '<=', $request->date_to);
        }

        if ($request->filled('teacher_id')) {
            $query->where(function($q) use ($request) {
                $q->where('original_teacher_id', $request->teacher_id)
                  ->orWhere('substitute_teacher_id', $request->teacher_id);
            });
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('emergency')) {
            $query->where('is_emergency', $request->emergency);
        }

        $substitutions = $query->orderBy('substitution_date', 'desc')
                              ->orderBy('start_time', 'desc')
                              ->paginate(20);

        $teachers = Teacher::where('is_active', true)->get();
        // Fix N+1 query by adding eager loading for students relationship
        $classes = ClassModel::with(['students'])->get();

        return view('substitution.substitutions', compact('substitutions', 'teachers', 'classes'));
    }

    /**
     * Create a new substitution request
     */
    public function store(Request $request)
    {
        $request->validate([
            'absence_id' => 'nullable|exists:teacher_absences,id',
            'original_teacher_id' => 'required|exists:teachers,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'substitution_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'period_number' => 'required|integer|min:1|max:10',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'preparation_materials' => 'nullable|string|max:2000',
            'priority' => 'required|in:low,medium,high,urgent',
            'is_emergency' => 'boolean',
        ]);

        $substitution = TeacherSubstitution::create([
            'absence_id' => $request->absence_id,
            'original_teacher_id' => $request->original_teacher_id,
            'class_id' => $request->class_id,
            'subject_id' => $request->subject_id,
            'period_number' => $request->period_number,
            'substitution_date' => $request->substitution_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => TeacherSubstitution::STATUS_PENDING,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'preparation_materials' => $request->preparation_materials,
            'priority' => $request->priority,
            'is_emergency' => $request->boolean('is_emergency'),
            'assigned_by' => Auth::id(),
        ]);

        // Try to auto-assign if requested
        if ($request->boolean('auto_assign')) {
            $assignedTeacher = TeacherSubstitution::autoAssignSubstitute($substitution->id);
            
            if ($assignedTeacher) {
                // Send notification to assigned teacher
                $this->sendSubstitutionNotification($substitution, $assignedTeacher);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Substitution created and automatically assigned to ' . $assignedTeacher->name,
                    'substitution' => $substitution->load(['originalTeacher', 'substituteTeacher', 'class', 'subject'])
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Substitution request created successfully',
            'substitution' => $substitution->load(['originalTeacher', 'class', 'subject'])
        ]);
    }

    /**
     * Find available substitute teachers
     */
    public function findAvailableTeachers(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'subject_id' => 'nullable|exists:subjects,id',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        $availableTeachers = TeacherSubstitution::findAvailableSubstitutes(
            $request->date,
            $request->start_time,
            $request->end_time,
            $request->subject_id,
            $request->class_id
        );

        // Add additional information for each teacher
        $teachersWithInfo = $availableTeachers->map(function ($teacher) use ($request) {
            $todaySubstitutions = TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
                ->where('substitution_date', $request->date)
                ->whereIn('status', [TeacherSubstitution::STATUS_CONFIRMED, TeacherSubstitution::STATUS_COMPLETED])
                ->count();

            $performance = TeacherSubstitution::getTeacherPerformance($teacher->id, 3);

            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'subjects' => $teacher->subjects->pluck('name')->toArray(),
                'classes' => $teacher->classes->pluck('name')->toArray(),
                'today_substitutions' => $todaySubstitutions,
                'reliability_score' => $performance['reliability_score'],
                'average_rating' => $performance['average_rating'],
                'total_substitutions' => $performance['total_substitutions'],
            ];
        });

        return response()->json([
            'success' => true,
            'available_teachers' => $teachersWithInfo,
            'count' => $teachersWithInfo->count()
        ]);
    }

    /**
     * Assign a substitute teacher
     */
    public function assignSubstitute(Request $request, $id)
    {
        $request->validate([
            'substitute_teacher_id' => 'required|exists:teachers,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $substitution = TeacherSubstitution::findOrFail($id);

        if (!$substitution->canBeAssigned()) {
            return response()->json([
                'success' => false,
                'message' => 'This substitution cannot be assigned at this time'
            ], 400);
        }

        // Check for conflicts
        $hasConflict = $substitution->hasConflict(
            $substitution->substitution_date,
            $substitution->start_time,
            $substitution->end_time
        );

        if ($hasConflict) {
            return response()->json([
                'success' => false,
                'message' => 'The selected teacher has a conflicting assignment'
            ], 400);
        }

        $substitution->update([
            'substitute_teacher_id' => $request->substitute_teacher_id,
            'status' => TeacherSubstitution::STATUS_PENDING,
            'assigned_at' => now(),
            'assigned_by' => Auth::id(),
            'notes' => $request->notes,
        ]);

        $substituteTeacher = Teacher::find($request->substitute_teacher_id);
        
        // Send notification
        $this->sendSubstitutionNotification($substitution, $substituteTeacher);

        return response()->json([
            'success' => true,
            'message' => 'Substitute teacher assigned successfully',
            'substitution' => $substitution->load(['originalTeacher', 'substituteTeacher', 'class', 'subject'])
        ]);
    }

    /**
     * Confirm substitution (by substitute teacher)
     */
    public function confirmSubstitution(Request $request, $id)
    {
        $substitution = TeacherSubstitution::findOrFail($id);

        if ($substitution->status !== TeacherSubstitution::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'This substitution cannot be confirmed'
            ], 400);
        }

        $substitution->markAsConfirmed(Auth::id());

        // Send confirmation notification to original teacher
        $this->notificationService->sendConfirmationNotification($substitution);

        return response()->json([
            'success' => true,
            'message' => 'Substitution confirmed successfully',
            'substitution' => $substitution->load(['originalTeacher', 'substituteTeacher', 'class', 'subject'])
        ]);
    }

    /**
     * Decline substitution (by substitute teacher)
     */
    public function declineSubstitution(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $substitution = TeacherSubstitution::findOrFail($id);

        if ($substitution->status !== TeacherSubstitution::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'This substitution cannot be declined'
            ], 400);
        }

        $substitution->markAsDeclined();
        
        if ($request->filled('reason')) {
            $substitution->update(['notes' => $request->reason]);
        }

        // Try to auto-assign to another teacher
        $newAssignment = TeacherSubstitution::autoAssignSubstitute($substitution->id);
        
        $message = 'Substitution declined successfully';
        if ($newAssignment) {
            $message .= '. Automatically reassigned to ' . $newAssignment->name;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'substitution' => $substitution->load(['originalTeacher', 'substituteTeacher', 'class', 'subject'])
        ]);
    }

    /**
     * Complete substitution
     */
    public function completeSubstitution(Request $request, $id)
    {
        $request->validate([
            'feedback' => 'nullable|string|max:2000',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $substitution = TeacherSubstitution::findOrFail($id);

        if (!in_array($substitution->status, [TeacherSubstitution::STATUS_CONFIRMED])) {
            return response()->json([
                'success' => false,
                'message' => 'This substitution cannot be completed'
            ], 400);
        }

        $substitution->markAsCompleted($request->feedback, $request->rating);

        return response()->json([
            'success' => true,
            'message' => 'Substitution completed successfully',
            'substitution' => $substitution->load(['originalTeacher', 'substituteTeacher', 'class', 'subject'])
        ]);
    }

    /**
     * Cancel substitution
     */
    public function cancelSubstitution(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $substitution = TeacherSubstitution::findOrFail($id);

        if (in_array($substitution->status, [TeacherSubstitution::STATUS_COMPLETED, TeacherSubstitution::STATUS_CANCELLED])) {
            return response()->json([
                'success' => false,
                'message' => 'This substitution cannot be cancelled'
            ], 400);
        }

        $reason = $request->get('reason', 'No reason provided');
        $substitution->markAsCancelled();

        // Send cancellation notification
        $this->notificationService->sendCancellationNotification($substitution, $reason);

        return response()->json([
            'success' => true,
            'message' => 'Substitution cancelled successfully',
            'substitution' => $substitution->load(['originalTeacher', 'substituteTeacher', 'class', 'subject'])
        ]);
    }

    /**
     * Get substitution statistics
     */
    public function getStatistics(Request $request)
    {
        $teacherId = $request->get('teacher_id');
        $startDate = $request->get('start_date', Carbon::now()->subMonths(3)->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $stats = TeacherSubstitution::getSubstitutionStats($teacherId, $startDate, $endDate);

        // Additional analytics
        $monthlyStats = TeacherSubstitution::select(
                DB::raw('YEAR(substitution_date) as year'),
                DB::raw('MONTH(substitution_date) as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN is_emergency = 1 THEN 1 ELSE 0 END) as emergency')
            )
            ->whereBetween('substitution_date', [$startDate, $endDate])
            ->when($teacherId, function($query) use ($teacherId) {
                return $query->where('substitute_teacher_id', $teacherId);
            })
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'monthly_stats' => $monthlyStats,
        ]);
    }

    /**
     * Get teacher performance metrics
     */
    public function getTeacherPerformance($teacherId)
    {
        $performance = TeacherSubstitution::getTeacherPerformance($teacherId);
        
        return response()->json([
            'success' => true,
            'performance' => $performance
        ]);
    }

    /**
     * Send notification to substitute teacher
     */
    private function sendSubstitutionNotification($substitution, $teacher)
    {
        // Use the new notification service to send comprehensive notifications
        $this->notificationService->sendAssignmentNotification($substitution, $teacher);
        
        // Schedule reminder notification 15 minutes before class
        $this->notificationService->sendReminderNotification($substitution, 15);
    }

    /**
     * Get my substitutions (for substitute teachers)
     */
    public function mySubstitutions(Request $request)
    {
        // Validate date range parameters
        $request->validate(array_merge(
            $this->getFilterDateRangeValidationRules(),
            [
                'status' => 'nullable|string|in:pending,assigned,confirmed,completed,cancelled',
            ]
        ), $this->getDateRangeValidationMessages());

        $teacherId = Auth::user()->teacher->id ?? null;
        
        if (!$teacherId) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher profile not found'
            ], 404);
        }

        $query = TeacherSubstitution::with(['originalTeacher', 'class', 'subject', 'absence'])
            ->where('substitute_teacher_id', $teacherId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('substitution_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('substitution_date', '<=', $request->date_to);
        }

        $substitutions = $query->orderBy('substitution_date', 'desc')
                              ->orderBy('start_time', 'desc')
                              ->paginate(20);

        return response()->json([
            'success' => true,
            'substitutions' => $substitutions
        ]);
    }

    /**
     * Auto-assign substitute teachers for pending substitutions
     */
    public function autoAssign(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'absence_id' => 'nullable|exists:teacher_absences,id',
            'date' => 'nullable|date',
            'subject_id' => 'nullable|exists:subjects,id',
            'class_id' => 'nullable|exists:class_models,id',
            'priority_criteria' => 'nullable|in:subject_expertise,availability,workload,performance'
        ]);

        $query = TeacherSubstitution::where('status', 'pending');

        // Filter by specific absence if provided
        if (isset($validated['absence_id'])) {
            $query->where('absence_id', $validated['absence_id']);
        }

        // Filter by date if provided
        if (isset($validated['date'])) {
            $query->where('substitution_date', $validated['date']);
        }

        // Filter by subject if provided
        if (isset($validated['subject_id'])) {
            $query->where('subject_id', $validated['subject_id']);
        }

        // Filter by class if provided
        if (isset($validated['class_id'])) {
            $query->where('class_id', $validated['class_id']);
        }

        $pendingSubstitutions = $query->with(['originalTeacher', 'class', 'subject', 'absence'])->get();

        $assignedCount = 0;
        $failedAssignments = [];
        $priorityCriteria = $validated['priority_criteria'] ?? 'subject_expertise';

        foreach ($pendingSubstitutions as $substitution) {
            try {
                // Find available substitute teachers
                $availableTeachers = $this->findAvailableTeachersHelper(
                    $substitution->substitution_date,
                    $substitution->start_time,
                    $substitution->end_time,
                    $substitution->subject_id,
                    $substitution->class_id,
                    $priorityCriteria
                );

                if ($availableTeachers->isNotEmpty()) {
                    $bestMatch = $availableTeachers->first();
                    
                    $substitution->update([
                        'substitute_teacher_id' => $bestMatch->id,
                        'status' => 'assigned',
                        'assigned_at' => now(),
                        'assigned_by' => auth()->id(),
                        'auto_assigned' => true
                    ]);

                    // Send notification to substitute teacher
                    $this->sendSubstitutionNotification($substitution, $bestMatch);
                    
                    $assignedCount++;
                } else {
                    $failedAssignments[] = [
                        'substitution_id' => $substitution->id,
                        'reason' => 'No available teachers found',
                        'class' => $substitution->class->name ?? 'Unknown',
                        'subject' => $substitution->subject->name ?? 'Unknown',
                        'date' => $substitution->substitution_date,
                        'time' => $substitution->start_time . ' - ' . $substitution->end_time
                    ];
                }
            } catch (\Exception $e) {
                $failedAssignments[] = [
                    'substitution_id' => $substitution->id,
                    'reason' => 'Assignment error: ' . $e->getMessage(),
                    'class' => $substitution->class->name ?? 'Unknown',
                    'subject' => $substitution->subject->name ?? 'Unknown',
                    'date' => $substitution->substitution_date,
                    'time' => $substitution->start_time . ' - ' . $substitution->end_time
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Auto-assignment completed. {$assignedCount} substitutions assigned successfully.",
            'data' => [
                'total_pending' => $pendingSubstitutions->count(),
                'assigned_count' => $assignedCount,
                'failed_count' => count($failedAssignments),
                'failed_assignments' => $failedAssignments,
                'priority_criteria_used' => $priorityCriteria
            ]
        ]);
    }

    /**
     * Find free teachers available for substitution
     */
    public function findFreeTeachers(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'subject_id' => 'nullable|exists:subjects,id',
            'class_id' => 'nullable|exists:class_models,id',
            'exclude_teacher_id' => 'nullable|exists:teachers,id',
            'priority_criteria' => 'nullable|in:subject_expertise,availability,workload,performance'
        ]);

        $availableTeachers = $this->findAvailableTeachersHelper(
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['subject_id'] ?? null,
            $validated['class_id'] ?? null,
            $validated['priority_criteria'] ?? 'subject_expertise',
            $validated['exclude_teacher_id'] ?? null
        );

        // Get additional information for each teacher
        $teachersWithDetails = $availableTeachers->map(function ($teacher) use ($validated) {
            $workload = TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
                ->where('substitution_date', '>=', Carbon::now()->startOfMonth())
                ->where('status', '!=', 'cancelled')
                ->count();

            $recentPerformance = TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
                ->where('substitution_date', '>=', Carbon::now()->subDays(30))
                ->whereNotNull('feedback_rating')
                ->avg('feedback_rating');

            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'department' => $teacher->department,
                'subject_expertise' => $teacher->subjects->pluck('name')->toArray(),
                'current_workload' => $workload,
                'recent_performance_rating' => round($recentPerformance, 2),
                'availability_score' => $teacher->availability_score ?? 0,
                'is_preferred_substitute' => $teacher->is_preferred_substitute ?? false,
                'last_substitution_date' => TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
                    ->latest('substitution_date')
                    ->value('substitution_date')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'available_teachers' => $teachersWithDetails,
                'total_count' => $teachersWithDetails->count(),
                'search_criteria' => [
                    'date' => $validated['date'],
                    'time_slot' => $validated['start_time'] . ' - ' . $validated['end_time'],
                    'subject_filter' => $validated['subject_id'] ? Subject::find($validated['subject_id'])->name : 'Any',
                    'class_filter' => $validated['class_id'] ? ClassModel::find($validated['class_id'])->name : 'Any',
                    'priority_criteria' => $validated['priority_criteria'] ?? 'subject_expertise'
                ]
            ]
        ]);
    }

    /**
     * Helper method to find available teachers based on criteria
     */
    private function findAvailableTeachersHelper($date, $startTime, $endTime, $subjectId = null, $classId = null, $priorityCriteria = 'subject_expertise', $excludeTeacherId = null)
    {
        $query = Teacher::with(['subjects', 'availability'])
            ->where('is_active', true)
            ->where('can_substitute', true);

        // Exclude specific teacher if provided
        if ($excludeTeacherId) {
            $query->where('id', '!=', $excludeTeacherId);
        }

        // Check for teachers not on leave
        $query->whereDoesntHave('absences', function ($q) use ($date) {
            $q->where('start_date', '<=', $date)
              ->where('end_date', '>=', $date)
              ->where('status', 'approved');
        });

        // Check for teachers not already assigned substitutions at this time
        $query->whereDoesntHave('substitutionAssignments', function ($q) use ($date, $startTime, $endTime) {
            $q->where('substitution_date', $date)
              ->where('status', '!=', 'cancelled')
              ->where(function ($timeQuery) use ($startTime, $endTime) {
                  $timeQuery->whereBetween('start_time', [$startTime, $endTime])
                           ->orWhereBetween('end_time', [$startTime, $endTime])
                           ->orWhere(function ($overlapQuery) use ($startTime, $endTime) {
                               $overlapQuery->where('start_time', '<=', $startTime)
                                          ->where('end_time', '>=', $endTime);
                           });
              });
        });

        // Filter by subject expertise if subject is specified
        if ($subjectId) {
            $query->whereHas('subjects', function ($q) use ($subjectId) {
                $q->where('subjects.id', $subjectId);
            });
        }

        $availableTeachers = $query->get();

        // Apply priority sorting based on criteria
        switch ($priorityCriteria) {
            case 'subject_expertise':
                if ($subjectId) {
                    $availableTeachers = $availableTeachers->sortByDesc(function ($teacher) use ($subjectId) {
                        return $teacher->subjects->contains('id', $subjectId) ? 1 : 0;
                    });
                }
                break;

            case 'availability':
                $availableTeachers = $availableTeachers->sortByDesc('availability_score');
                break;

            case 'workload':
                $availableTeachers = $availableTeachers->sortBy(function ($teacher) {
                    return TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
                        ->where('substitution_date', '>=', Carbon::now()->startOfMonth())
                        ->count();
                });
                break;

            case 'performance':
                $availableTeachers = $availableTeachers->sortByDesc(function ($teacher) {
                    return TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
                        ->where('substitution_date', '>=', Carbon::now()->subDays(30))
                        ->whereNotNull('feedback_rating')
                        ->avg('feedback_rating') ?? 0;
                });
                break;
        }

        return $availableTeachers->values();
    }

    /**
     * Get notifications for substitute teachers
     */
    public function getNotifications(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get notifications for the authenticated user
            $notifications = Notification::where('user_id', $user->id)
                ->whereIn('type', [
                    'substitution_assignment',
                    'substitute_confirmed',
                    'substitution_reminder',
                    'substitution_cancelled',
                    'emergency_assignment'
                ])
                ->where('read_at', null)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($notification) {
                    $data = json_decode($notification->data, true) ?? [];
                    
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'priority' => $notification->priority ?? 'medium',
                        'created_at' => $notification->created_at->toISOString(),
                        'show_browser_notification' => true,
                        'action_url' => $this->getNotificationActionUrl($notification->type, $data),
                        'actions' => $this->getNotificationActions($notification->type, $data)
                    ];
                });

            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get substitute notifications', [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications'
            ], 500);
        }
    }

    /**
     * Handle notification action (confirm, decline, etc.)
     */
    public function handleNotificationAction(Request $request, $notificationId)
    {
        try {
            $notification = Notification::findOrFail($notificationId);
            $action = $request->input('action');
            $data = json_decode($notification->data, true) ?? [];

            // Handle different actions based on notification type
            switch ($notification->type) {
                case 'substitution_assignment':
                    if ($action === 'confirm') {
                        $substitution = TeacherSubstitution::find($data['substitution_id']);
                        if ($substitution) {
                            $substitution->update(['status' => 'confirmed']);
                            $this->notificationService->sendConfirmationNotification($substitution);
                        }
                        $message = 'Substitution confirmed successfully';
                    } elseif ($action === 'decline') {
                        $substitution = TeacherSubstitution::find($data['substitution_id']);
                        if ($substitution) {
                            $substitution->update(['status' => 'declined']);
                        }
                        $message = 'Substitution declined';
                    }
                    break;

                case 'substitution_reminder':
                    if ($action === 'acknowledge') {
                        $message = 'Reminder acknowledged';
                    }
                    break;

                default:
                    $message = 'Action processed';
            }

            // Mark notification as read
            $notification->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => $message ?? 'Action completed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle notification action', [
                'notification_id' => $notificationId,
                'action' => $request->input('action'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process action'
            ], 500);
        }
    }

    /**
     * Dismiss a notification
     */
    public function dismissNotification(Request $request, $notificationId)
    {
        try {
            $notification = Notification::where('id', $notificationId)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            $notification->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Notification dismissed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to dismiss notification'
            ], 500);
        }
    }

    /**
     * Clear all notifications for the user
     */
    public function clearAllNotifications(Request $request)
    {
        try {
            Notification::where('user_id', $request->user()->id)
                ->whereIn('type', [
                    'substitution_assignment',
                    'substitute_confirmed',
                    'substitution_reminder',
                    'substitution_cancelled',
                    'emergency_assignment'
                ])
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'All notifications cleared'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications'
            ], 500);
        }
    }

    /**
     * Get action URL for notification
     */
    private function getNotificationActionUrl($type, $data)
    {
        switch ($type) {
            case 'substitution_assignment':
            case 'substitution_reminder':
                return isset($data['substitution_id']) 
                    ? route('substitutions.show', $data['substitution_id'])
                    : route('substitutions.index');
            
            default:
                return route('substitutions.index');
        }
    }

    /**
     * Get available actions for notification
     */
    private function getNotificationActions($type, $data)
    {
        switch ($type) {
            case 'substitution_assignment':
                return [
                    [
                        'action' => 'confirm',
                        'text' => 'Confirm',
                        'class' => 'btn-success',
                        'icon' => 'fas fa-check'
                    ],
                    [
                        'action' => 'decline',
                        'text' => 'Decline',
                        'class' => 'btn-danger',
                        'icon' => 'fas fa-times'
                    ]
                ];

            case 'substitution_reminder':
                return [
                    [
                        'action' => 'acknowledge',
                        'text' => 'Got it',
                        'class' => 'btn-info',
                        'icon' => 'fas fa-check'
                    ]
                ];

            default:
                return [];
        }
    }
}