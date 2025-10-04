<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherSubstitution;
use App\Models\TeacherAbsence;
use App\Models\BellTiming;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FreePeriodDetectionService
{
    /**
     * Find teachers with free periods at a specific time
     */
    public function findFreeTeachers($date, $startTime, $endTime, $excludeTeacherId = null): Collection
    {
        $freeTeachers = collect();
        
        // Get all active teachers who can substitute
        $teachers = Teacher::where('is_active', true)
            ->where('can_substitute', true)
            ->when($excludeTeacherId, function ($query, $excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->get();

        foreach ($teachers as $teacher) {
            if ($this->isTeacherFree($teacher, $date, $startTime, $endTime)) {
                $freeTeachers->push($teacher);
            }
        }

        return $freeTeachers;
    }

    /**
     * Check if a specific teacher is free during the given time period
     */
    public function isTeacherFree(Teacher $teacher, $date, $startTime, $endTime): bool
    {
        // Check if teacher is on leave
        if ($this->isTeacherOnLeave($teacher, $date)) {
            return false;
        }

        // Check if teacher has conflicting substitutions
        if ($this->hasConflictingSubstitutions($teacher, $date, $startTime, $endTime)) {
            return false;
        }

        // Check teacher availability
        if (!$this->isTeacherAvailable($teacher, $date, $startTime, $endTime)) {
            return false;
        }

        // Check daily substitution limits
        if (!$this->canTakeMoreSubstitutions($teacher, $date)) {
            return false;
        }

        return true;
    }

    /**
     * Get real-time free period information for all teachers
     */
    public function getRealTimeFreeTeachers($date = null): array
    {
        $date = $date ?? Carbon::today()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i');
        
        // Get current bell schedule
        $currentSchedule = BellTiming::getCurrentSchedule();
        $currentPeriod = $this->getCurrentPeriod($currentSchedule, $currentTime);
        
        if (!$currentPeriod) {
            return [
                'current_period' => null,
                'free_teachers' => [],
                'message' => 'No active period at this time'
            ];
        }

        // Find teachers free during current period
        $freeTeachers = $this->findFreeTeachers(
            $date,
            $currentPeriod['start_time'],
            $currentPeriod['end_time']
        );

        // Get detailed information for each free teacher
        $freeTeachersWithDetails = $freeTeachers->map(function ($teacher) use ($date) {
            return [
                'id' => $teacher->id,
                'name' => $teacher->user->name ?? $teacher->name,
                'email' => $teacher->user->email ?? $teacher->email,
                'subjects' => $teacher->subjects->pluck('name')->toArray(),
                'current_workload' => $this->getTodaySubstitutionCount($teacher, $date),
                'max_substitutions' => $this->getMaxSubstitutionsPerDay($teacher),
                'availability_status' => $this->getAvailabilityStatus($teacher, $date),
                'last_substitution' => $this->getLastSubstitutionTime($teacher, $date),
                'expertise_score' => $this->calculateExpertiseScore($teacher)
            ];
        });

        return [
            'current_period' => $currentPeriod,
            'free_teachers' => $freeTeachersWithDetails->toArray(),
            'total_free_teachers' => $freeTeachers->count(),
            'timestamp' => Carbon::now()->toISOString()
        ];
    }

    /**
     * Find the best substitute teacher for a specific requirement
     */
    public function findBestSubstitute($date, $startTime, $endTime, $subjectId = null, $classId = null, $priorityCriteria = 'subject_expertise'): ?Teacher
    {
        $freeTeachers = $this->findFreeTeachers($date, $startTime, $endTime);
        
        if ($freeTeachers->isEmpty()) {
            return null;
        }

        // Score and rank teachers based on criteria
        $scoredTeachers = $freeTeachers->map(function ($teacher) use ($subjectId, $classId, $priorityCriteria, $date) {
            $score = $this->calculateTeacherScore($teacher, $subjectId, $classId, $priorityCriteria, $date);
            return [
                'teacher' => $teacher,
                'score' => $score
            ];
        });

        // Sort by score (highest first) and return the best match
        $bestMatch = $scoredTeachers->sortByDesc('score')->first();
        
        return $bestMatch ? $bestMatch['teacher'] : null;
    }

    /**
     * Get upcoming free periods for a teacher
     */
    public function getUpcomingFreePeriods(Teacher $teacher, $date = null, $days = 7): array
    {
        $date = $date ?? Carbon::today();
        $endDate = Carbon::parse($date)->addDays($days);
        $freePeriods = [];

        $currentDate = Carbon::parse($date);
        while ($currentDate->lte($endDate)) {
            if (!$currentDate->isWeekend()) {
                $dayFreePeriods = $this->getDayFreePeriods($teacher, $currentDate->format('Y-m-d'));
                if (!empty($dayFreePeriods)) {
                    $freePeriods[$currentDate->format('Y-m-d')] = $dayFreePeriods;
                }
            }
            $currentDate->addDay();
        }

        return $freePeriods;
    }

    /**
     * Check if teacher is on leave
     */
    private function isTeacherOnLeave(Teacher $teacher, $date): bool
    {
        return TeacherAbsence::where('teacher_id', $teacher->id)
            ->where('absence_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $date);
            })
            ->where('status', 'approved')
            ->exists();
    }

    /**
     * Check for conflicting substitutions
     */
    private function hasConflictingSubstitutions(Teacher $teacher, $date, $startTime, $endTime): bool
    {
        return TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
            ->where('substitution_date', $date)
            ->whereIn('status', ['confirmed', 'assigned', 'pending'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime]);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>=', $endTime);
                });
            })
            ->exists();
    }

    /**
     * Check teacher availability
     */
    private function isTeacherAvailable(Teacher $teacher, $date, $startTime, $endTime): bool
    {
        $availability = TeacherAvailability::where('teacher_id', $teacher->id)
            ->where('date', $date)
            ->where('status', 'available')
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $endTime)
            ->first();

        return $availability !== null;
    }

    /**
     * Check if teacher can take more substitutions today
     */
    private function canTakeMoreSubstitutions(Teacher $teacher, $date): bool
    {
        $todayCount = $this->getTodaySubstitutionCount($teacher, $date);
        $maxAllowed = $this->getMaxSubstitutionsPerDay($teacher);
        
        return $todayCount < $maxAllowed;
    }

    /**
     * Get today's substitution count for teacher
     */
    private function getTodaySubstitutionCount(Teacher $teacher, $date): int
    {
        return TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
            ->where('substitution_date', $date)
            ->whereIn('status', ['confirmed', 'assigned', 'completed'])
            ->count();
    }

    /**
     * Get maximum substitutions per day for teacher
     */
    private function getMaxSubstitutionsPerDay(Teacher $teacher): int
    {
        $availability = TeacherAvailability::where('teacher_id', $teacher->id)
            ->where('date', Carbon::today()->format('Y-m-d'))
            ->first();

        return $availability->max_substitutions_per_day ?? 3;
    }

    /**
     * Get current period from bell schedule
     */
    private function getCurrentPeriod($schedule, $currentTime): ?array
    {
        $currentTimeCarbon = Carbon::createFromFormat('H:i', $currentTime);
        
        for ($i = 0; $i < $schedule->count() - 1; $i++) {
            $currentBell = $schedule[$i];
            $nextBell = $schedule[$i + 1];
            
            $currentBellTime = Carbon::createFromFormat('H:i', $currentBell->time->format('H:i'));
            $nextBellTime = Carbon::createFromFormat('H:i', $nextBell->time->format('H:i'));
            
            if ($currentTimeCarbon->between($currentBellTime, $nextBellTime)) {
                return [
                    'name' => $currentBell->name,
                    'start_time' => $currentBell->time->format('H:i'),
                    'end_time' => $nextBell->time->format('H:i'),
                    'type' => $currentBell->type,
                    'description' => $currentBell->description
                ];
            }
        }

        return null;
    }

    /**
     * Calculate teacher score for substitution assignment
     */
    private function calculateTeacherScore(Teacher $teacher, $subjectId, $classId, $priorityCriteria, $date): int
    {
        $score = 0;

        // Subject expertise (highest priority)
        if ($subjectId && $teacher->subjects()->where('subject_id', $subjectId)->exists()) {
            $score += 50;
        }

        // Class familiarity
        if ($classId && $teacher->classes()->where('class_id', $classId)->exists()) {
            $score += 30;
        }

        // Workload consideration (prefer less busy teachers)
        $todaySubstitutions = $this->getTodaySubstitutionCount($teacher, $date);
        $score += (5 - $todaySubstitutions) * 5; // Max 25 points

        // Experience and performance
        $score += min($teacher->experience_years ?? 0, 10) * 2; // Max 20 points

        // Availability preference
        $availability = TeacherAvailability::where('teacher_id', $teacher->id)
            ->where('date', $date)
            ->first();
        
        if ($availability && $availability->can_substitute) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Calculate expertise score for teacher
     */
    private function calculateExpertiseScore(Teacher $teacher): int
    {
        $score = 0;
        
        // Subject count
        $score += $teacher->subjects->count() * 5;
        
        // Experience
        $score += min($teacher->experience_years ?? 0, 20);
        
        // Performance rating (if available)
        $score += ($teacher->performance_rating ?? 3) * 5;
        
        return $score;
    }

    /**
     * Get availability status for teacher
     */
    private function getAvailabilityStatus(Teacher $teacher, $date): string
    {
        $availability = TeacherAvailability::where('teacher_id', $teacher->id)
            ->where('date', $date)
            ->first();

        return $availability ? $availability->status : 'unknown';
    }

    /**
     * Get last substitution time for teacher today
     */
    private function getLastSubstitutionTime(Teacher $teacher, $date): ?string
    {
        $lastSubstitution = TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
            ->where('substitution_date', $date)
            ->whereIn('status', ['confirmed', 'assigned', 'completed'])
            ->orderBy('end_time', 'desc')
            ->first();

        return $lastSubstitution ? $lastSubstitution->end_time : null;
    }

    /**
     * Get free periods for a specific day
     */
    private function getDayFreePeriods(Teacher $teacher, $date): array
    {
        $schedule = BellTiming::getCurrentSchedule();
        $freePeriods = [];

        foreach ($schedule as $index => $bell) {
            if ($index < $schedule->count() - 1) {
                $nextBell = $schedule[$index + 1];
                $startTime = $bell->time->format('H:i');
                $endTime = $nextBell->time->format('H:i');

                if ($this->isTeacherFree($teacher, $date, $startTime, $endTime)) {
                    $freePeriods[] = [
                        'period_name' => $bell->name,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'duration_minutes' => Carbon::createFromFormat('H:i', $endTime)
                            ->diffInMinutes(Carbon::createFromFormat('H:i', $startTime))
                    ];
                }
            }
        }

        return $freePeriods;
    }
}