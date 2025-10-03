<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherAbsence;
use App\Models\TeacherSubstitution;
use App\Models\Teacher;
use App\Models\ClassModel;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubstitutionController extends Controller
{
    /**
     * Display the substitution dashboard
     */
    public function index()
    {
        $stats = TeacherSubstitution::getDashboardStats();
        $todaySubstitutions = TeacherSubstitution::getTodaySubstitutions();
        $emergencySubstitutions = TeacherSubstitution::getEmergencySubstitutions();
        $upcomingSubstitutions = TeacherSubstitution::getUpcomingSubstitutions(null, 7);

        return view('substitution.dashboard', compact(
            'stats',
            'todaySubstitutions',
            'emergencySubstitutions',
            'upcomingSubstitutions'
        ));
    }

    /**
     * Show all substitutions with filtering
     */
    public function substitutions(Request $request)
    {
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
        $classes = ClassModel::all();

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
    public function cancelSubstitution($id)
    {
        $substitution = TeacherSubstitution::findOrFail($id);

        if (in_array($substitution->status, [TeacherSubstitution::STATUS_COMPLETED, TeacherSubstitution::STATUS_CANCELLED])) {
            return response()->json([
                'success' => false,
                'message' => 'This substitution cannot be cancelled'
            ], 400);
        }

        $substitution->markAsCancelled();

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
        // This would integrate with your notification system
        // For now, we'll just mark that notification was sent
        $substitution->update(['notification_sent' => true]);
        
        // TODO: Implement actual notification sending
        // - Email notification
        // - SMS notification
        // - Push notification (if mobile app exists)
        // - In-app notification
    }

    /**
     * Get my substitutions (for substitute teachers)
     */
    public function mySubstitutions(Request $request)
    {
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
}