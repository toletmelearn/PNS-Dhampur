<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\TeacherSubstitution;
use App\Models\TeacherAbsence;
use App\Models\BellTiming;
use App\Models\Notification;
use App\Services\FreePeriodDetectionService;
use App\Services\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConflictResolutionService
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
     * Resolve conflicts for multiple teacher absences on the same day
     */
    public function resolveMultipleAbsenceConflicts($date = null): array
    {
        $date = $date ?? Carbon::today();
        
        $results = [
            'date' => $date->format('Y-m-d'),
            'total_conflicts' => 0,
            'resolved_conflicts' => 0,
            'unresolved_conflicts' => 0,
            'strategies_used' => [],
            'conflicts' => [],
            'resolutions' => []
        ];

        // Find all pending substitution requests for the date
        $pendingRequests = TeacherSubstitution::where('substitution_date', $date)
            ->where('status', 'pending')
            ->with(['originalTeacher', 'class', 'subject'])
            ->orderBy('priority', 'desc')
            ->orderBy('is_emergency', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        if ($pendingRequests->isEmpty()) {
            return $results;
        }

        // Group overlapping requests
        $conflictGroups = $this->groupOverlappingRequests($pendingRequests);
        $results['total_conflicts'] = $conflictGroups->count();

        foreach ($conflictGroups as $groupIndex => $group) {
            $conflictResolution = $this->resolveConflictGroup($group, $date);
            
            $results['conflicts'][] = [
                'group_id' => $groupIndex,
                'requests_count' => $group->count(),
                'requests' => $group->pluck('id')->toArray(),
                'resolution' => $conflictResolution
            ];

            if ($conflictResolution['success']) {
                $results['resolved_conflicts']++;
                $results['resolutions'][] = $conflictResolution;
                $results['strategies_used'] = array_unique(
                    array_merge($results['strategies_used'], $conflictResolution['strategies_used'])
                );
            } else {
                $results['unresolved_conflicts']++;
            }
        }

        return $results;
    }

    /**
     * Group overlapping substitution requests
     */
    private function groupOverlappingRequests(Collection $requests): Collection
    {
        $groups = collect();
        $processed = collect();

        foreach ($requests as $request) {
            if ($processed->contains($request->id)) {
                continue;
            }

            $group = collect([$request]);
            $processed->push($request->id);

            // Find overlapping requests
            foreach ($requests as $otherRequest) {
                if ($processed->contains($otherRequest->id)) {
                    continue;
                }

                if ($this->requestsOverlap($request, $otherRequest)) {
                    $group->push($otherRequest);
                    $processed->push($otherRequest->id);
                }
            }

            if ($group->count() > 1) {
                $groups->push($group);
            }
        }

        return $groups;
    }

    /**
     * Check if two requests overlap in time
     */
    private function requestsOverlap(TeacherSubstitution $request1, TeacherSubstitution $request2): bool
    {
        $start1 = Carbon::createFromFormat('H:i', $request1->start_time);
        $end1 = Carbon::createFromFormat('H:i', $request1->end_time);
        $start2 = Carbon::createFromFormat('H:i', $request2->start_time);
        $end2 = Carbon::createFromFormat('H:i', $request2->end_time);

        return $start1->lt($end2) && $start2->lt($end1);
    }

    /**
     * Resolve conflicts within a group of overlapping requests
     */
    private function resolveConflictGroup(Collection $group, Carbon $date): array
    {
        $strategies = [];
        $resolutions = [];
        $success = false;

        // Strategy 1: Priority-based sequential assignment
        $priorityResolution = $this->resolveBypriority($group, $date);
        $strategies[] = 'priority_based';
        
        if ($priorityResolution['success']) {
            $resolutions[] = $priorityResolution;
            $success = true;
        }

        // Strategy 2: Time slot optimization
        if (!$success) {
            $timeSlotResolution = $this->resolveByTimeSlotOptimization($group, $date);
            $strategies[] = 'time_slot_optimization';
            
            if ($timeSlotResolution['success']) {
                $resolutions[] = $timeSlotResolution;
                $success = true;
            }
        }

        // Strategy 3: Teacher pool expansion
        if (!$success) {
            $poolExpansion = $this->resolveByPoolExpansion($group, $date);
            $strategies[] = 'pool_expansion';
            
            if ($poolExpansion['success']) {
                $resolutions[] = $poolExpansion;
                $success = true;
            }
        }

        // Strategy 4: Schedule modification
        if (!$success) {
            $scheduleModification = $this->resolveByScheduleModification($group, $date);
            $strategies[] = 'schedule_modification';
            
            if ($scheduleModification['success']) {
                $resolutions[] = $scheduleModification;
                $success = true;
            }
        }

        // Strategy 5: Emergency protocols
        if (!$success) {
            $emergencyResolution = $this->resolveByEmergencyProtocols($group, $date);
            $strategies[] = 'emergency_protocols';
            
            if ($emergencyResolution['success']) {
                $resolutions[] = $emergencyResolution;
                $success = true;
            }
        }

        return [
            'success' => $success,
            'group_size' => $group->count(),
            'strategies_used' => $strategies,
            'resolutions' => $resolutions,
            'unresolved_requests' => $success ? [] : $group->pluck('id')->toArray()
        ];
    }

    /**
     * Resolve conflicts by priority
     */
    private function resolveBypriority(Collection $group, Carbon $date): array
    {
        $resolved = [];
        $failed = [];

        // Sort by priority and emergency status
        $sortedRequests = $group->sortByDesc(function ($request) {
            $priorityScore = match($request->priority) {
                'high' => 3,
                'medium' => 2,
                'low' => 1,
                default => 0
            };
            
            return ($request->is_emergency ? 10 : 0) + $priorityScore;
        });

        foreach ($sortedRequests as $request) {
            $availableTeachers = $this->freePeriodService->findFreeTeachers(
                $date,
                $request->start_time,
                $request->end_time,
                $request->original_teacher_id
            );

            if ($availableTeachers->isNotEmpty()) {
                $bestTeacher = $this->findBestTeacher($availableTeachers, $request);
                
                if ($bestTeacher) {
                    $resolved[] = [
                        'request_id' => $request->id,
                        'teacher_id' => $bestTeacher->id,
                        'teacher_name' => $bestTeacher->user->name ?? $bestTeacher->name,
                        'strategy' => 'priority_assignment'
                    ];

                    // Temporarily mark as assigned to prevent double assignment
                    $request->update([
                        'substitute_teacher_id' => $bestTeacher->id,
                        'status' => 'assigned',
                        'assigned_at' => now()
                    ]);
                }
            } else {
                $failed[] = [
                    'request_id' => $request->id,
                    'reason' => 'No available teachers'
                ];
            }
        }

        return [
            'success' => count($resolved) > 0,
            'resolved_count' => count($resolved),
            'failed_count' => count($failed),
            'resolved' => $resolved,
            'failed' => $failed
        ];
    }

    /**
     * Resolve conflicts by optimizing time slots
     */
    private function resolveByTimeSlotOptimization(Collection $group, Carbon $date): array
    {
        $resolved = [];
        
        // Try to find non-overlapping time slots for requests
        $timeSlots = $this->generateOptimalTimeSlots($group, $date);
        
        foreach ($timeSlots as $slot) {
            $request = $group->find($slot['request_id']);
            if (!$request) continue;

            $availableTeachers = $this->freePeriodService->findFreeTeachers(
                $date,
                $slot['start_time'],
                $slot['end_time'],
                $request->original_teacher_id
            );

            if ($availableTeachers->isNotEmpty()) {
                $bestTeacher = $this->findBestTeacher($availableTeachers, $request);
                
                if ($bestTeacher) {
                    // Update request with optimized time slot
                    $request->update([
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'substitute_teacher_id' => $bestTeacher->id,
                        'status' => 'assigned',
                        'assigned_at' => now(),
                        'notes' => ($request->notes ?? '') . ' [Time slot optimized]'
                    ]);

                    $resolved[] = [
                        'request_id' => $request->id,
                        'teacher_id' => $bestTeacher->id,
                        'original_time' => $request->start_time . '-' . $request->end_time,
                        'optimized_time' => $slot['start_time'] . '-' . $slot['end_time'],
                        'strategy' => 'time_slot_optimization'
                    ];
                }
            }
        }

        return [
            'success' => count($resolved) > 0,
            'resolved_count' => count($resolved),
            'resolved' => $resolved,
            'optimization_applied' => true
        ];
    }

    /**
     * Generate optimal time slots for conflicting requests
     */
    private function generateOptimalTimeSlots(Collection $group, Carbon $date): array
    {
        $slots = [];
        $bellTimings = BellTiming::where('season', $this->getCurrentSeason($date))
            ->orderBy('start_time')
            ->get();

        foreach ($group as $request) {
            // Find the best available period for this request
            $requestDuration = Carbon::createFromFormat('H:i', $request->start_time)
                ->diffInMinutes(Carbon::createFromFormat('H:i', $request->end_time));

            foreach ($bellTimings as $timing) {
                $timingDuration = Carbon::createFromFormat('H:i', $timing->start_time)
                    ->diffInMinutes(Carbon::createFromFormat('H:i', $timing->end_time));

                if ($timingDuration >= $requestDuration) {
                    // Check if this slot is available
                    $conflictingRequests = TeacherSubstitution::where('substitution_date', $date)
                        ->where('id', '!=', $request->id)
                        ->where('status', 'assigned')
                        ->where(function ($query) use ($timing) {
                            $query->where(function ($q) use ($timing) {
                                $q->whereBetween('start_time', [$timing->start_time, $timing->end_time])
                                  ->orWhereBetween('end_time', [$timing->start_time, $timing->end_time]);
                            });
                        })
                        ->count();

                    if ($conflictingRequests === 0) {
                        $slots[] = [
                            'request_id' => $request->id,
                            'start_time' => $timing->start_time,
                            'end_time' => $timing->end_time,
                            'period_name' => $timing->period_name,
                            'priority_score' => $this->calculateSlotPriorityScore($request, $timing)
                        ];
                        break; // Found a slot for this request
                    }
                }
            }
        }

        // Sort by priority score
        usort($slots, function ($a, $b) {
            return $b['priority_score'] <=> $a['priority_score'];
        });

        return $slots;
    }

    /**
     * Calculate priority score for time slot assignment
     */
    private function calculateSlotPriorityScore(TeacherSubstitution $request, BellTiming $timing): int
    {
        $score = 0;

        // Emergency requests get highest priority
        if ($request->is_emergency) {
            $score += 100;
        }

        // Priority level scoring
        $score += match($request->priority) {
            'high' => 50,
            'medium' => 30,
            'low' => 10,
            default => 0
        };

        // Subject-period matching
        if ($request->subject && str_contains(strtolower($timing->period_name), strtolower($request->subject))) {
            $score += 20;
        }

        // Time preference (morning periods often preferred)
        $timingHour = Carbon::createFromFormat('H:i', $timing->start_time)->hour;
        if ($timingHour >= 8 && $timingHour <= 11) {
            $score += 15;
        }

        return $score;
    }

    /**
     * Resolve conflicts by expanding teacher pool
     */
    private function resolveByPoolExpansion(Collection $group, Carbon $date): array
    {
        $resolved = [];
        
        // Include part-time teachers, retired teachers, and admin staff
        $expandedPool = Teacher::where('status', 'active')
            ->orWhere('status', 'part_time')
            ->orWhere('status', 'retired')
            ->with('user')
            ->get();

        foreach ($group as $request) {
            $suitableTeachers = $expandedPool->filter(function ($teacher) use ($request, $date) {
                return $this->isTeacherSuitableForExpansion($teacher, $request, $date);
            });

            if ($suitableTeachers->isNotEmpty()) {
                $bestTeacher = $this->findBestTeacher($suitableTeachers, $request);
                
                if ($bestTeacher) {
                    $request->update([
                        'substitute_teacher_id' => $bestTeacher->id,
                        'status' => 'assigned',
                        'assigned_at' => now(),
                        'notes' => ($request->notes ?? '') . ' [Assigned from expanded pool]'
                    ]);

                    $resolved[] = [
                        'request_id' => $request->id,
                        'teacher_id' => $bestTeacher->id,
                        'teacher_status' => $bestTeacher->status,
                        'strategy' => 'pool_expansion'
                    ];
                }
            }
        }

        return [
            'success' => count($resolved) > 0,
            'resolved_count' => count($resolved),
            'resolved' => $resolved,
            'expanded_pool_size' => $expandedPool->count()
        ];
    }

    /**
     * Check if teacher is suitable for expanded pool assignment
     */
    private function isTeacherSuitableForExpansion(Teacher $teacher, TeacherSubstitution $request, Carbon $date): bool
    {
        // Check basic availability
        if (!$this->freePeriodService->isTeacherAvailable($teacher, $date, $request->start_time, $request->end_time)) {
            return false;
        }

        // For retired teachers, check if they're willing to substitute
        if ($teacher->status === 'retired' && !$teacher->available_for_substitution) {
            return false;
        }

        // For part-time teachers, check their schedule
        if ($teacher->status === 'part_time') {
            $partTimeSchedule = $teacher->part_time_schedule ?? [];
            $dayOfWeek = $date->format('l');
            
            if (!in_array($dayOfWeek, $partTimeSchedule)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve conflicts by modifying schedules
     */
    private function resolveByScheduleModification(Collection $group, Carbon $date): array
    {
        $resolved = [];
        
        // Try to combine classes or modify lesson plans
        foreach ($group as $request) {
            $modifications = $this->findScheduleModifications($request, $date);
            
            foreach ($modifications as $modification) {
                if ($this->applyScheduleModification($modification, $request)) {
                    $resolved[] = [
                        'request_id' => $request->id,
                        'modification_type' => $modification['type'],
                        'details' => $modification['details'],
                        'strategy' => 'schedule_modification'
                    ];
                    break;
                }
            }
        }

        return [
            'success' => count($resolved) > 0,
            'resolved_count' => count($resolved),
            'resolved' => $resolved
        ];
    }

    /**
     * Find possible schedule modifications
     */
    private function findScheduleModifications(TeacherSubstitution $request, Carbon $date): array
    {
        $modifications = [];

        // Option 1: Combine with another class
        $similarRequests = TeacherSubstitution::where('substitution_date', $date)
            ->where('id', '!=', $request->id)
            ->where('subject', $request->subject)
            ->where('status', 'pending')
            ->get();

        foreach ($similarRequests as $similarRequest) {
            if ($this->canCombineClasses($request, $similarRequest)) {
                $modifications[] = [
                    'type' => 'class_combination',
                    'details' => [
                        'primary_request' => $request->id,
                        'secondary_request' => $similarRequest->id,
                        'combined_class' => $request->class->name . ' + ' . $similarRequest->class->name
                    ]
                ];
            }
        }

        // Option 2: Move to different time slot
        $availableSlots = $this->findAvailableTimeSlots($date, $request);
        foreach ($availableSlots as $slot) {
            $modifications[] = [
                'type' => 'time_shift',
                'details' => [
                    'original_time' => $request->start_time . '-' . $request->end_time,
                    'new_time' => $slot['start_time'] . '-' . $slot['end_time'],
                    'period_name' => $slot['period_name']
                ]
            ];
        }

        // Option 3: Split into smaller sessions
        if ($this->canSplitSession($request)) {
            $modifications[] = [
                'type' => 'session_split',
                'details' => [
                    'original_duration' => $this->getSessionDuration($request),
                    'split_sessions' => $this->calculateSplitSessions($request)
                ]
            ];
        }

        return $modifications;
    }

    /**
     * Apply schedule modification
     */
    private function applyScheduleModification(array $modification, TeacherSubstitution $request): bool
    {
        try {
            DB::beginTransaction();

            switch ($modification['type']) {
                case 'class_combination':
                    return $this->applyCombinedClassModification($modification, $request);
                
                case 'time_shift':
                    return $this->applyTimeShiftModification($modification, $request);
                
                case 'session_split':
                    return $this->applySessionSplitModification($modification, $request);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to apply schedule modification', [
                'modification' => $modification,
                'request_id' => $request->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Resolve conflicts using emergency protocols
     */
    private function resolveByEmergencyProtocols(Collection $group, Carbon $date): array
    {
        $resolved = [];
        
        // Emergency protocol 1: Admin coverage
        $adminStaff = Teacher::whereHas('user', function ($query) {
            $query->where('role', 'admin')
                  ->orWhere('role', 'principal')
                  ->orWhere('role', 'vice_principal');
        })->get();

        foreach ($group as $request) {
            if ($request->is_emergency || $request->priority === 'high') {
                foreach ($adminStaff as $admin) {
                    if ($this->freePeriodService->isTeacherAvailable($admin, $date, $request->start_time, $request->end_time)) {
                        $request->update([
                            'substitute_teacher_id' => $admin->id,
                            'status' => 'assigned',
                            'assigned_at' => now(),
                            'notes' => ($request->notes ?? '') . ' [Emergency admin coverage]'
                        ]);

                        $resolved[] = [
                            'request_id' => $request->id,
                            'teacher_id' => $admin->id,
                            'protocol' => 'admin_coverage',
                            'strategy' => 'emergency_protocols'
                        ];
                        break;
                    }
                }
            }
        }

        // Emergency protocol 2: Class cancellation with notification
        foreach ($group as $request) {
            if (!in_array($request->id, array_column($resolved, 'request_id'))) {
                $this->handleEmergencyCancellation($request);
                
                $resolved[] = [
                    'request_id' => $request->id,
                    'protocol' => 'emergency_cancellation',
                    'strategy' => 'emergency_protocols'
                ];
            }
        }

        return [
            'success' => count($resolved) > 0,
            'resolved_count' => count($resolved),
            'resolved' => $resolved,
            'protocols_used' => array_unique(array_column($resolved, 'protocol'))
        ];
    }

    /**
     * Handle emergency cancellation
     */
    private function handleEmergencyCancellation(TeacherSubstitution $request): void
    {
        $request->update([
            'status' => 'cancelled',
            'notes' => ($request->notes ?? '') . ' [Emergency cancellation - no substitute available]'
        ]);

        // Notify relevant parties
        $this->sendEmergencyCancellationNotifications($request);
    }

    /**
     * Send emergency cancellation notifications
     */
    private function sendEmergencyCancellationNotifications(TeacherSubstitution $request): void
    {
        // Notify students/parents
        if ($request->class) {
            $students = $request->class->students ?? collect();
            
            foreach ($students as $student) {
                if ($student->parent_contact) {
                    $this->pushNotificationService->sendEmergencyNotification(
                        $student->parent_contact,
                        "Class Cancelled",
                        "The {$request->subject} class for {$request->class->name} on {$request->substitution_date} has been cancelled due to teacher unavailability."
                    );
                }
            }
        }

        // Notify administration
        $adminUsers = \App\Models\User::where('role', 'admin')->get();
        foreach ($adminUsers as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'Emergency Class Cancellation',
                'message' => "Class {$request->class->name} - {$request->subject} cancelled due to no available substitute",
                'type' => 'emergency_cancellation',
                'priority' => 'high'
            ]);
        }
    }

    /**
     * Find best teacher from available options
     */
    private function findBestTeacher(Collection $teachers, TeacherSubstitution $request): ?Teacher
    {
        if ($teachers->isEmpty()) {
            return null;
        }

        return $teachers->sortByDesc(function ($teacher) use ($request) {
            $score = 0;

            // Subject expertise
            if ($request->subject_id && $teacher->subjects()->where('subject_id', $request->subject_id)->exists()) {
                $score += 50;
            }

            // Class familiarity
            if ($request->class_id && $teacher->classes()->where('class_id', $request->class_id)->exists()) {
                $score += 30;
            }

            // Experience
            $score += min($teacher->experience_years ?? 0, 10) * 2;

            // Performance rating
            $score += ($teacher->performance_rating ?? 3) * 3;

            return $score;
        })->first();
    }

    /**
     * Get current season
     */
    private function getCurrentSeason(Carbon $date): string
    {
        $month = $date->month;
        
        if ($month >= 4 && $month <= 6) {
            return 'summer';
        } elseif ($month >= 7 && $month <= 10) {
            return 'monsoon';
        } else {
            return 'winter';
        }
    }

    /**
     * Helper methods for schedule modifications
     */
    private function canCombineClasses(TeacherSubstitution $request1, TeacherSubstitution $request2): bool
    {
        // Check if classes can be combined (same subject, similar level, etc.)
        return $request1->subject === $request2->subject && 
               abs($request1->class->grade_level - $request2->class->grade_level) <= 1;
    }

    private function findAvailableTimeSlots(Carbon $date, TeacherSubstitution $request): array
    {
        $bellTimings = BellTiming::where('season', $this->getCurrentSeason($date))
            ->orderBy('start_time')
            ->get();

        $availableSlots = [];
        
        foreach ($bellTimings as $timing) {
            $conflictingRequests = TeacherSubstitution::where('substitution_date', $date)
                ->where('status', 'assigned')
                ->where(function ($query) use ($timing) {
                    $query->whereBetween('start_time', [$timing->start_time, $timing->end_time])
                          ->orWhereBetween('end_time', [$timing->start_time, $timing->end_time]);
                })
                ->count();

            if ($conflictingRequests === 0) {
                $availableSlots[] = [
                    'start_time' => $timing->start_time,
                    'end_time' => $timing->end_time,
                    'period_name' => $timing->period_name
                ];
            }
        }

        return $availableSlots;
    }

    private function canSplitSession(TeacherSubstitution $request): bool
    {
        $duration = Carbon::createFromFormat('H:i', $request->start_time)
            ->diffInMinutes(Carbon::createFromFormat('H:i', $request->end_time));
        
        return $duration >= 90; // Can split if 90+ minutes
    }

    private function getSessionDuration(TeacherSubstitution $request): int
    {
        return Carbon::createFromFormat('H:i', $request->start_time)
            ->diffInMinutes(Carbon::createFromFormat('H:i', $request->end_time));
    }

    private function calculateSplitSessions(TeacherSubstitution $request): array
    {
        $duration = $this->getSessionDuration($request);
        $sessionCount = ceil($duration / 45); // 45-minute sessions
        
        $sessions = [];
        $currentTime = Carbon::createFromFormat('H:i', $request->start_time);
        
        for ($i = 0; $i < $sessionCount; $i++) {
            $endTime = $currentTime->copy()->addMinutes(45);
            $sessions[] = [
                'start_time' => $currentTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'session_number' => $i + 1
            ];
            $currentTime = $endTime->copy()->addMinutes(5); // 5-minute break
        }
        
        return $sessions;
    }

    private function applyCombinedClassModification(array $modification, TeacherSubstitution $request): bool
    {
        // Implementation for combined class modification
        return true;
    }

    private function applyTimeShiftModification(array $modification, TeacherSubstitution $request): bool
    {
        $request->update([
            'start_time' => $modification['details']['new_time']['start_time'],
            'end_time' => $modification['details']['new_time']['end_time'],
            'notes' => ($request->notes ?? '') . ' [Time shifted]'
        ]);
        return true;
    }

    private function applySessionSplitModification(array $modification, TeacherSubstitution $request): bool
    {
        // Implementation for session split modification
        return true;
    }
}