<?php

namespace App\Http\Controllers;

use App\Models\TeacherSubstitution;
use App\Models\TeacherAvailability;
use App\Models\Teacher;
use App\Models\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TeacherSubstitutionController extends Controller
{
    /**
     * Display a listing of substitution requests
     */
    public function index(Request $request): JsonResponse
    {
        $query = TeacherSubstitution::with([
            'absentTeacher.user',
            'substituteTeacher.user',
            'class',
            'requestedBy',
            'assignedBy'
        ]);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        } else {
            // Default to upcoming substitutions
            $query->upcoming();
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by emergency
        if ($request->has('emergency')) {
            $query->where('is_emergency', $request->boolean('emergency'));
        }

        $substitutions = $query->orderBy('date')
                              ->orderBy('start_time')
                              ->orderBy('priority', 'desc')
                              ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $substitutions,
        ]);
    }

    /**
     * Store a new substitution request
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'absent_teacher_id' => 'required|exists:teachers,id',
            'class_id' => 'required|exists:class_models,id',
            'substitution_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'subject' => 'nullable|string|max:255',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'priority' => 'in:low,medium,high',
            'is_emergency' => 'boolean',
        ]);

        // Map API field to both columns for compatibility
        $validated['date'] = $validated['substitution_date'];
        // Keep substitution_date as well for model methods relying on it
        $validated['substitution_date'] = $validated['date'];

        $validated['requested_at'] = now();
        $validated['requested_by'] = auth()->id();
        $validated['status'] = 'pending';

        $substitution = TeacherSubstitution::create($validated);

        // Try to auto-assign if not emergency (emergency requires manual review)
        if (!$validated['is_emergency']) {
            $this->attemptAutoAssignment($substitution->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Substitution request created successfully',
            'data' => $substitution->load([
                'absentTeacher.user',
                'substituteTeacher.user',
                'class'
            ]),
        ], 201);
    }

    /**
     * Display the specified substitution request
     */
    public function show(TeacherSubstitution $substitution): JsonResponse
    {
        $substitution->load([
            'absentTeacher.user',
            'substituteTeacher.user',
            'class',
            'requestedBy',
            'assignedBy'
        ]);

        return response()->json([
            'success' => true,
            'data' => $substitution,
        ]);
    }

    /**
     * Update the specified substitution request
     */
    public function update(Request $request, TeacherSubstitution $substitution): JsonResponse
    {
        $validated = $request->validate([
            'substitute_teacher_id' => 'nullable|exists:teachers,id',
            'date' => 'date|after_or_equal:today',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i|after:start_time',
            'subject' => 'nullable|string|max:255',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'priority' => 'in:low,medium,high',
            'status' => 'in:pending,assigned,completed,cancelled',
        ]);

        // Handle status changes
        if (isset($validated['status'])) {
            switch ($validated['status']) {
                case 'assigned':
                    if (!$substitution->substitute_teacher_id && !isset($validated['substitute_teacher_id'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot assign without a substitute teacher',
                        ], 400);
                    }
                    $validated['assigned_at'] = now();
                    $validated['assigned_by'] = auth()->id();
                    break;
                case 'completed':
                    $validated['completed_at'] = now();
                    break;
            }
        }

        $substitution->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Substitution request updated successfully',
            'data' => $substitution->load([
                'absentTeacher.user',
                'substituteTeacher.user',
                'class'
            ]),
        ]);
    }

    /**
     * Remove the specified substitution request
     */
    public function destroy(TeacherSubstitution $substitution): JsonResponse
    {
        if ($substitution->status === 'assigned' && Carbon::parse($substitution->date)->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete assigned substitution on the same day',
            ], 400);
        }

        $substitution->delete();

        return response()->json([
            'success' => true,
            'message' => 'Substitution request deleted successfully',
        ]);
    }

    /**
     * Get available substitute teachers for a specific request
     */
    public function getAvailableSubstitutes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'subject' => 'nullable|string',
            'absent_teacher_id' => 'nullable|exists:teachers,id',
        ]);

        $availableTeachers = TeacherSubstitution::findAvailableSubstitutes(
            $validated['date'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['subject'] ?? null
        );

        // Exclude the absent teacher
        if (isset($validated['absent_teacher_id'])) {
            $availableTeachers = $availableTeachers->where('id', '!=', $validated['absent_teacher_id']);
        }

        $teachersWithDetails = $availableTeachers->map(function ($teacher) use ($validated) {
            $availability = $teacher->availability()
                                   ->where('date', $validated['date'])
                                   ->first();
            
            $currentSubstitutions = TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
                                                      ->where('date', $validated['date'])
                                                      ->whereIn('status', ['assigned', 'completed'])
                                                      ->count();

            return [
                'id' => $teacher->id,
                'name' => $teacher->user->name,
                'email' => $teacher->user->email,
                'experience_years' => $teacher->experience_years,
                'qualification' => $teacher->qualification,
                'subject_expertise' => $availability->subject_expertise ?? null,
                'current_substitutions' => $currentSubstitutions,
                'max_substitutions' => $availability->max_substitutions_per_day ?? 3,
                'can_take_more' => $currentSubstitutions < ($availability->max_substitutions_per_day ?? 3),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $teachersWithDetails->values(),
        ]);
    }

    /**
     * Assign a substitute teacher to a request
     */
    public function assignSubstitute(Request $request, TeacherSubstitution $substitution): JsonResponse
    {
        $validated = $request->validate([
            'substitute_teacher_id' => 'required|exists:teachers,id',
        ]);

        if ($substitution->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Can only assign substitutes to pending requests',
            ], 400);
        }

        // Check if the substitute teacher is available
        $availableTeachers = TeacherSubstitution::findAvailableSubstitutes(
            $substitution->date,
            $substitution->start_time,
            $substitution->end_time,
            $substitution->subject
        );

        if (!$availableTeachers->contains('id', $validated['substitute_teacher_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selected teacher is not available for this time slot',
            ], 400);
        }

        $substitution->markAsAssigned($validated['substitute_teacher_id'], auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Substitute teacher assigned successfully',
            'data' => $substitution->load([
                'absentTeacher.user',
                'substituteTeacher.user',
                'class'
            ]),
        ]);
    }

    /**
     * Auto-assign substitute teachers to pending requests
     */
    public function autoAssignSubstitutes(): JsonResponse
    {
        $pendingSubstitutions = TeacherSubstitution::pending()
                                                  ->where('is_emergency', false)
                                                  ->upcoming()
                                                  ->get();

        $assigned = 0;
        $failed = [];

        foreach ($pendingSubstitutions as $substitution) {
            if (TeacherSubstitution::autoAssignSubstitute($substitution->id)) {
                $assigned++;
            } else {
                $failed[] = [
                    'id' => $substitution->id,
                    'reason' => 'No available substitute teachers found',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Auto-assignment completed. {$assigned} substitutions assigned.",
            'data' => [
                'assigned_count' => $assigned,
                'failed_assignments' => $failed,
            ],
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): JsonResponse
    {
        $today = Carbon::today();
        
        $stats = [
            'today' => [
                'total' => TeacherSubstitution::whereDate('date', $today)->count(),
                'pending' => TeacherSubstitution::whereDate('date', $today)->where('status', 'pending')->count(),
                'assigned' => TeacherSubstitution::whereDate('date', $today)->where('status', 'assigned')->count(),
                'completed' => TeacherSubstitution::whereDate('date', $today)->where('status', 'completed')->count(),
                'emergency' => TeacherSubstitution::whereDate('date', $today)->where('is_emergency', true)->count(),
            ],
            'this_week' => [
                'total' => TeacherSubstitution::whereBetween('date', [
                    $today->startOfWeek(),
                    $today->copy()->endOfWeek()
                ])->count(),
                'pending' => TeacherSubstitution::whereBetween('date', [
                    $today->startOfWeek(),
                    $today->copy()->endOfWeek()
                ])->where('status', 'pending')->count(),
            ],
            'overdue' => TeacherSubstitution::pending()
                                           ->where('date', '<', $today)
                                           ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Attempt to auto-assign a substitute teacher
     */
    private function attemptAutoAssignment($substitutionId): bool
    {
        return TeacherSubstitution::autoAssignSubstitute($substitutionId);
    }
}