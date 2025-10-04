<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\TeacherSubstitution;
use App\Models\TeacherAbsence;
use App\Models\Notification;
use App\Services\FreePeriodDetectionService;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoTeacherAssignmentService
{
    protected FreePeriodDetectionService $freePeriodService;
    protected PushNotificationService $pushNotificationService;

    public function __construct(
        FreePeriodDetectionService $freePeriodService,
        PushNotificationService $pushNotificationService
    ) {
        $this->freePeriodService = $freePeriodService;
        $this->pushNotificationService = $pushNotificationService;
    }

    /**
     * Automatically assign substitutes for pending requests
     */
    public function autoAssignSubstitutes($priorityCriteria = 'subject_expertise', $dryRun = false): array
    {
        $results = [
            'assigned' => 0,
            'failed' => 0,
            'conflicts_resolved' => 0,
            'assignments' => [],
            'failures' => [],
            'conflicts' => []
        ];

        // Get pending substitution requests
        $pendingRequests = TeacherSubstitution::where('status', 'pending')
            ->where('substitution_date', '>=', Carbon::today())
            ->orderBy('priority', 'desc')
            ->orderBy('is_emergency', 'desc')
            ->orderBy('substitution_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        foreach ($pendingRequests as $request) {
            try {
                $assignment = $this->assignSubstituteForRequest($request, $priorityCriteria, $dryRun);
                
                if ($assignment['success']) {
                    $results['assigned']++;
                    $results['assignments'][] = $assignment;
                    
                    if (isset($assignment['conflicts_resolved'])) {
                        $results['conflicts_resolved'] += $assignment['conflicts_resolved'];
                        $results['conflicts'] = array_merge($results['conflicts'], $assignment['conflicts']);
                    }
                } else {
                    $results['failed']++;
                    $results['failures'][] = [
                        'request_id' => $request->id,
                        'reason' => $assignment['reason'],
                        'details' => $assignment
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['failures'][] = [
                    'request_id' => $request->id,
                    'reason' => 'System error: ' . $e->getMessage(),
                    'exception' => $e->getTraceAsString()
                ];
                
                Log::error('Auto assignment failed for request ' . $request->id, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $results;
    }

    /**
     * Assign substitute for a specific request
     */
    public function assignSubstituteForRequest(TeacherSubstitution $request, $priorityCriteria = 'subject_expertise', $dryRun = false): array
    {
        // Find available teachers
        $availableTeachers = $this->freePeriodService->findFreeTeachers(
            $request->substitution_date,
            $request->start_time,
            $request->end_time,
            $request->original_teacher_id
        );

        if ($availableTeachers->isEmpty()) {
            // Try conflict resolution
            $conflictResolution = $this->resolveConflicts($request);
            
            if ($conflictResolution['success']) {
                $availableTeachers = collect([$conflictResolution['teacher']]);
                $conflictsResolved = $conflictResolution['conflicts_resolved'];
            } else {
                return [
                    'success' => false,
                    'reason' => 'No available teachers found',
                    'request_id' => $request->id,
                    'available_count' => 0,
                    'conflict_resolution_attempted' => true,
                    'conflict_resolution_result' => $conflictResolution
                ];
            }
        }

        // Score and rank teachers
        $bestTeacher = $this->findBestMatch($availableTeachers, $request, $priorityCriteria);

        if (!$bestTeacher) {
            return [
                'success' => false,
                'reason' => 'No suitable teacher found after scoring',
                'request_id' => $request->id,
                'available_count' => $availableTeachers->count()
            ];
        }

        // Perform assignment
        if (!$dryRun) {
            DB::beginTransaction();
            try {
                $request->update([
                    'substitute_teacher_id' => $bestTeacher->id,
                    'status' => 'assigned',
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id() ?? 1, // System assignment
                    'auto_assigned' => true
                ]);

                // Send notifications
                $this->sendAssignmentNotifications($request, $bestTeacher);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }

        $result = [
            'success' => true,
            'request_id' => $request->id,
            'substitute_teacher_id' => $bestTeacher->id,
            'substitute_teacher_name' => $bestTeacher->user->name ?? $bestTeacher->name,
            'assignment_score' => $this->calculateAssignmentScore($bestTeacher, $request),
            'dry_run' => $dryRun
        ];

        if (isset($conflictsResolved)) {
            $result['conflicts_resolved'] = $conflictsResolved;
            $result['conflicts'] = $conflictResolution['conflicts'] ?? [];
        }

        return $result;
    }

    /**
     * Resolve conflicts for teacher assignment
     */
    public function resolveConflicts(TeacherSubstitution $request): array
    {
        $conflicts = [];
        $resolvedConflicts = 0;

        // Strategy 1: Find teachers with lower priority assignments
        $conflictingAssignments = TeacherSubstitution::where('substitution_date', $request->substitution_date)
            ->where('status', 'assigned')
            ->where('priority', '<', $request->priority)
            ->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time]);
                })->orWhere(function ($q) use ($request) {
                    $q->where('start_time', '<=', $request->start_time)
                      ->where('end_time', '>=', $request->end_time);
                });
            })
            ->with('substituteTeacher')
            ->get();

        foreach ($conflictingAssignments as $conflictingAssignment) {
            if ($this->canReassignConflictingRequest($conflictingAssignment, $request)) {
                // Find alternative for the conflicting assignment
                $alternativeTeacher = $this->findAlternativeTeacher($conflictingAssignment, $request);
                
                if ($alternativeTeacher) {
                    $conflicts[] = [
                        'original_assignment_id' => $conflictingAssignment->id,
                        'original_teacher_id' => $conflictingAssignment->substitute_teacher_id,
                        'new_teacher_id' => $alternativeTeacher->id,
                        'reason' => 'Reassigned due to higher priority request'
                    ];

                    // Perform reassignment
                    $conflictingAssignment->update([
                        'substitute_teacher_id' => $alternativeTeacher->id,
                        'notes' => ($conflictingAssignment->notes ?? '') . ' [Auto-reassigned due to higher priority request]'
                    ]);

                    $resolvedConflicts++;

                    return [
                        'success' => true,
                        'teacher' => $conflictingAssignment->substituteTeacher,
                        'conflicts_resolved' => $resolvedConflicts,
                        'conflicts' => $conflicts
                    ];
                }
            }
        }

        // Strategy 2: Split long assignments
        $longAssignments = TeacherSubstitution::where('substitution_date', $request->substitution_date)
            ->where('status', 'assigned')
            ->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, end_time) > 120') // More than 2 hours
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->start_time)
                      ->where('end_time', '>', $request->end_time);
            })
            ->get();

        foreach ($longAssignments as $longAssignment) {
            if ($this->canSplitAssignment($longAssignment, $request)) {
                $splitResult = $this->splitAssignment($longAssignment, $request);
                
                if ($splitResult['success']) {
                    return [
                        'success' => true,
                        'teacher' => $longAssignment->substituteTeacher,
                        'conflicts_resolved' => 1,
                        'conflicts' => [$splitResult]
                    ];
                }
            }
        }

        // Strategy 3: Emergency reassignment for high priority requests
        if ($request->is_emergency || $request->priority === 'high') {
            $emergencyReassignment = $this->performEmergencyReassignment($request);
            
            if ($emergencyReassignment['success']) {
                return $emergencyReassignment;
            }
        }

        return [
            'success' => false,
            'reason' => 'No conflicts could be resolved',
            'strategies_attempted' => ['priority_reassignment', 'assignment_splitting', 'emergency_reassignment'],
            'conflicts_found' => $conflictingAssignments->count() + $longAssignments->count()
        ];
    }

    /**
     * Find the best matching teacher from available options
     */
    private function findBestMatch(Collection $teachers, TeacherSubstitution $request, $priorityCriteria): ?Teacher
    {
        if ($teachers->isEmpty()) {
            return null;
        }

        $scoredTeachers = $teachers->map(function ($teacher) use ($request, $priorityCriteria) {
            return [
                'teacher' => $teacher,
                'score' => $this->calculateAssignmentScore($teacher, $request, $priorityCriteria)
            ];
        });

        $bestMatch = $scoredTeachers->sortByDesc('score')->first();
        
        return $bestMatch['teacher'];
    }

    /**
     * Calculate assignment score for teacher-request pair
     */
    private function calculateAssignmentScore(Teacher $teacher, TeacherSubstitution $request, $priorityCriteria = 'subject_expertise'): int
    {
        $score = 0;

        // Subject expertise (highest weight)
        if ($request->subject_id && $teacher->subjects()->where('subject_id', $request->subject_id)->exists()) {
            $score += 50;
        }

        // Class familiarity
        if ($request->class_id && $teacher->classes()->where('class_id', $request->class_id)->exists()) {
            $score += 30;
        }

        // Workload consideration
        $todaySubstitutions = TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
            ->where('substitution_date', $request->substitution_date)
            ->whereIn('status', ['assigned', 'confirmed', 'completed'])
            ->count();
        
        $score += (5 - min($todaySubstitutions, 5)) * 5; // Max 25 points

        // Experience
        $score += min($teacher->experience_years ?? 0, 10) * 2; // Max 20 points

        // Performance rating
        $score += ($teacher->performance_rating ?? 3) * 3; // Max 15 points

        // Availability preference
        $score += 10; // Base availability score

        // Priority criteria specific scoring
        switch ($priorityCriteria) {
            case 'availability':
                $score += (5 - $todaySubstitutions) * 10;
                break;
            case 'workload':
                $score += (10 - $todaySubstitutions) * 5;
                break;
            case 'performance':
                $score += ($teacher->performance_rating ?? 3) * 10;
                break;
        }

        return $score;
    }

    /**
     * Check if a conflicting request can be reassigned
     */
    private function canReassignConflictingRequest(TeacherSubstitution $conflicting, TeacherSubstitution $newRequest): bool
    {
        // Don't reassign emergency requests
        if ($conflicting->is_emergency) {
            return false;
        }

        // Don't reassign if already confirmed by teacher
        if ($conflicting->status === 'confirmed') {
            return false;
        }

        // Check priority levels
        return $newRequest->priority > $conflicting->priority || $newRequest->is_emergency;
    }

    /**
     * Find alternative teacher for conflicting assignment
     */
    private function findAlternativeTeacher(TeacherSubstitution $conflicting, TeacherSubstitution $newRequest): ?Teacher
    {
        $alternativeTeachers = $this->freePeriodService->findFreeTeachers(
            $conflicting->substitution_date,
            $conflicting->start_time,
            $conflicting->end_time,
            $conflicting->substitute_teacher_id
        );

        return $this->findBestMatch($alternativeTeachers, $conflicting, 'subject_expertise');
    }

    /**
     * Check if assignment can be split
     */
    private function canSplitAssignment(TeacherSubstitution $assignment, TeacherSubstitution $request): bool
    {
        // Check if the assignment duration allows splitting
        $assignmentStart = Carbon::createFromFormat('H:i', $assignment->start_time);
        $assignmentEnd = Carbon::createFromFormat('H:i', $assignment->end_time);
        $requestStart = Carbon::createFromFormat('H:i', $request->start_time);
        $requestEnd = Carbon::createFromFormat('H:i', $request->end_time);

        // Ensure there's enough time before and after the request
        $beforeDuration = $requestStart->diffInMinutes($assignmentStart);
        $afterDuration = $assignmentEnd->diffInMinutes($requestEnd);

        return $beforeDuration >= 30 && $afterDuration >= 30; // At least 30 minutes each
    }

    /**
     * Split a long assignment to accommodate new request
     */
    private function splitAssignment(TeacherSubstitution $assignment, TeacherSubstitution $request): array
    {
        try {
            DB::beginTransaction();

            // Create first part (before the new request)
            $firstPart = $assignment->replicate();
            $firstPart->end_time = $request->start_time;
            $firstPart->notes = ($assignment->notes ?? '') . ' [Split assignment - Part 1]';
            $firstPart->save();

            // Create second part (after the new request)
            $secondPart = $assignment->replicate();
            $secondPart->start_time = $request->end_time;
            $secondPart->notes = ($assignment->notes ?? '') . ' [Split assignment - Part 2]';
            $secondPart->save();

            // Cancel original assignment
            $assignment->update([
                'status' => 'cancelled',
                'notes' => ($assignment->notes ?? '') . ' [Split into multiple assignments]'
            ]);

            DB::commit();

            return [
                'success' => true,
                'original_id' => $assignment->id,
                'first_part_id' => $firstPart->id,
                'second_part_id' => $secondPart->id,
                'reason' => 'Assignment split to accommodate higher priority request'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'reason' => 'Failed to split assignment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Perform emergency reassignment
     */
    private function performEmergencyReassignment(TeacherSubstitution $request): array
    {
        // Find any assigned teacher who could potentially be moved
        $reassignableRequests = TeacherSubstitution::where('substitution_date', $request->substitution_date)
            ->where('status', 'assigned')
            ->where('is_emergency', false)
            ->where('priority', '!=', 'high')
            ->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time]);
                })->orWhere(function ($q) use ($request) {
                    $q->where('start_time', '<=', $request->start_time)
                      ->where('end_time', '>=', $request->end_time);
                });
            })
            ->orderBy('priority', 'asc')
            ->get();

        foreach ($reassignableRequests as $reassignable) {
            // Try to find alternative or cancel the lower priority request
            $alternative = $this->findAlternativeTeacher($reassignable, $request);
            
            if ($alternative) {
                $reassignable->update([
                    'substitute_teacher_id' => $alternative->id,
                    'notes' => ($reassignable->notes ?? '') . ' [Emergency reassignment]'
                ]);

                return [
                    'success' => true,
                    'teacher' => $reassignable->substituteTeacher,
                    'conflicts_resolved' => 1,
                    'conflicts' => [[
                        'type' => 'emergency_reassignment',
                        'original_assignment_id' => $reassignable->id,
                        'new_teacher_id' => $alternative->id
                    ]]
                ];
            } else {
                // Cancel the lower priority request
                $reassignable->update([
                    'status' => 'cancelled',
                    'notes' => ($reassignable->notes ?? '') . ' [Cancelled for emergency request]'
                ]);

                return [
                    'success' => true,
                    'teacher' => $reassignable->substituteTeacher,
                    'conflicts_resolved' => 1,
                    'conflicts' => [[
                        'type' => 'emergency_cancellation',
                        'cancelled_assignment_id' => $reassignable->id
                    ]]
                ];
            }
        }

        return [
            'success' => false,
            'reason' => 'No emergency reassignment possible'
        ];
    }

    /**
     * Send assignment notifications
     */
    private function sendAssignmentNotifications(TeacherSubstitution $request, Teacher $teacher): void
    {
        try {
            // Create notification for substitute teacher
            Notification::create([
                'user_id' => $teacher->user_id,
                'title' => 'New Substitution Assignment',
                'message' => "You have been assigned to substitute for {$request->originalTeacher->user->name} on {$request->substitution_date} from {$request->start_time} to {$request->end_time}",
                'type' => 'substitution_assignment',
                'data' => json_encode([
                    'substitution_id' => $request->id,
                    'class' => $request->class->name ?? 'N/A',
                    'subject' => $request->subject ?? 'N/A'
                ]),
                'priority' => $request->is_emergency ? 'high' : 'medium'
            ]);

            // Send push notification
            $this->pushNotificationService->sendSubstitutionAssignment(
                $teacher,
                $request
            );

            // Notify original teacher
            if ($request->originalTeacher && $request->originalTeacher->user_id) {
                Notification::create([
                    'user_id' => $request->originalTeacher->user_id,
                    'title' => 'Substitute Assigned',
                    'message' => "{$teacher->user->name} has been assigned as your substitute on {$request->substitution_date}",
                    'type' => 'substitute_assigned',
                    'data' => json_encode([
                        'substitution_id' => $request->id,
                        'substitute_teacher' => $teacher->user->name
                    ])
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send assignment notifications', [
                'request_id' => $request->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get assignment statistics
     */
    public function getAssignmentStats($startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::today()->subDays(7);
        $endDate = $endDate ?? Carbon::today();

        $query = TeacherSubstitution::whereBetween('substitution_date', [$startDate, $endDate]);

        return [
            'total_requests' => $query->count(),
            'auto_assigned' => $query->where('auto_assigned', true)->count(),
            'manual_assigned' => $query->where('auto_assigned', false)->whereNotNull('substitute_teacher_id')->count(),
            'pending' => $query->where('status', 'pending')->count(),
            'completed' => $query->where('status', 'completed')->count(),
            'cancelled' => $query->where('status', 'cancelled')->count(),
            'emergency_requests' => $query->where('is_emergency', true)->count(),
            'average_assignment_time' => $query->whereNotNull('assigned_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, assigned_at)) as avg_time')
                ->value('avg_time'),
            'success_rate' => $query->count() > 0 
                ? ($query->whereNotNull('substitute_teacher_id')->count() / $query->count()) * 100 
                : 0
        ];
    }
}