<?php

namespace App\Services;

use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherSubstitution;
use App\Models\TeacherAbsence;
use App\Models\BellTiming;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Support\Constants;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FreePeriodDetectionService
{
    /**
     * Find teachers with free periods at a specific time with enhanced filtering
     */
    public function findFreeTeachers($date, $startTime, $endTime, $excludeTeacherId = null, $options = []): Collection
    {
        $freeTeachers = collect();
        
        // Enhanced query with better filtering
        $teachers = Teacher::where('is_active', true)
            ->where('can_substitute', true)
            ->when($excludeTeacherId, function ($query, $excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->with(['subjects', 'classes', 'user'])
            ->get();

        foreach ($teachers as $teacher) {
            if ($this->isTeacherFreeEnhanced($teacher, $date, $startTime, $endTime, $options)) {
                // Add compatibility score for sorting
                $teacher->compatibility_score = $this->calculateCompatibilityScore(
                    $teacher, 
                    $options['subject_id'] ?? null, 
                    $options['class_id'] ?? null,
                    $date
                );
                $freeTeachers->push($teacher);
            }
        }

        // Sort by compatibility score (highest first)
        return $freeTeachers->sortByDesc('compatibility_score');
    }

    /**
     * Enhanced teacher availability check with comprehensive validation
     */
    public function isTeacherFreeEnhanced(Teacher $teacher, $date, $startTime, $endTime, $options = []): bool
    {
        // Basic availability checks
        if (!$this->isTeacherFree($teacher, $date, $startTime, $endTime)) {
            return false;
        }

        // Enhanced workload balancing
        if (!$this->checkWorkloadBalance($teacher, $date, $options)) {
            return false;
        }

        // Teacher preference validation
        if (!$this->checkTeacherPreferences($teacher, $date, $startTime, $endTime, $options)) {
            return false;
        }

        // Subject compatibility check (if subject specified)
        if (isset($options['subject_id']) && !$this->hasSubjectCompatibility($teacher, $options['subject_id'])) {
            // Allow if teacher has general teaching capability or related subjects
            if (!$this->hasRelatedSubjectExpertise($teacher, $options['subject_id'])) {
                return false;
            }
        }

        return true;
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

        return $availability->max_substitutions_per_day ?? Constants::get('scoring.max_teacher_score', 3);
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
        $score = Constants::get('scoring.base_availability_score', 0);

        // Subject expertise (highest priority)
        if ($subjectId && $teacher->subjects()->where('id', $subjectId)->exists()) {
            $score += Constants::get('scoring.subject_compatibility_score', 50);
        }

        // Class familiarity
        if ($classId && $teacher->classes()->where('id', $classId)->exists()) {
            $score += Constants::get('scoring.class_familiarity_score', 30);
        }

        // Workload consideration (prefer less busy teachers)
        $todaySubstitutions = $this->getTodaySubstitutionCount($teacher, $date);
        $score += (Constants::get('scoring.workload_balance_score', 5) - $todaySubstitutions) * Constants::get('scoring.workload_balance_score', 5);

        // Experience and performance
        $score += min($teacher->experience_years ?? 0, Constants::get('scoring.max_experience_points', 10)) * Constants::get('scoring.experience_multiplier', 2);

        // Availability preference
        $availability = TeacherAvailability::where('teacher_id', $teacher->id)
            ->where('date', $date)
            ->first();
        
        if ($availability && $availability->can_substitute) {
            $score += Constants::get('scoring.base_availability_score', 10);
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
        $score += $teacher->subjects->count() * Constants::get('scoring.workload_balance_score', 5);
        
        // Experience
        $score += min($teacher->experience_years ?? 0, Constants::get('scoring.max_experience_points', 20));
        
        // Performance rating (if available)
        $score += ($teacher->performance_rating ?? 3) * Constants::get('scoring.workload_balance_score', 5);
        
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

    /**
     * Calculate comprehensive compatibility score for teacher selection
     */
    private function calculateCompatibilityScore(Teacher $teacher, $subjectId = null, $classId = null, $date = null): int
    {
        $score = 0;

        // Subject expertise scoring (0-100 points)
        if ($subjectId) {
            $subjectScore = $this->calculateSubjectCompatibilityScore($teacher, $subjectId);
            $score += $subjectScore;
        } else {
            $score += Constants::get('scoring.base_availability_score', 50); // Base score if no subject specified
        }

        // Class familiarity scoring (0-50 points)
        if ($classId) {
            $classScore = $this->calculateClassFamiliarityScore($teacher, $classId);
            $score += $classScore;
        } else {
            $score += Constants::get('scoring.max_experience_points', 25); // Base score if no class specified
        }

        // Workload balancing (0-30 points - higher score for less busy teachers)
        $workloadScore = $this->calculateWorkloadScore($teacher, $date);
        $score += $workloadScore;

        // Experience and performance (0-40 points)
        $experienceScore = $this->calculateExperienceScore($teacher);
        $score += $experienceScore;

        // Availability preference (0-20 points)
        $availabilityScore = $this->calculateAvailabilityPreferenceScore($teacher, $date);
        $score += $availabilityScore;

        // Recent performance rating (0-30 points)
        $performanceScore = $this->calculateRecentPerformanceScore($teacher);
        $score += $performanceScore;

        return $score;
    }

    /**
     * Calculate subject compatibility score with detailed analysis
     */
    private function calculateSubjectCompatibilityScore(Teacher $teacher, $subjectId): int
    {
        $score = 0;
        
        // Direct subject match (highest priority)
        if ($teacher->subjects()->where('id', $subjectId)->exists()) {
            $score += Constants::get('scoring.max_teacher_score', 100);
            
            // Add experience-based bonus
            $experienceYears = $teacher->experience ? ($teacher->experience->total_years ?? 0) : 0;
            $score += min($experienceYears * Constants::get('scoring.workload_balance_score', 5), Constants::get('scoring.max_experience_points', 25));
        } else {
            // Check for related subjects
            $relatedScore = $this->getRelatedSubjectScore($teacher, $subjectId);
            $score += $relatedScore;
        }

        return min($score, Constants::get('scoring.max_teacher_score', 100)); // Cap at 100
    }

    /**
     * Get score for related subject expertise
     */
    private function getRelatedSubjectScore(Teacher $teacher, $subjectId): int
    {
        $subject = Subject::find($subjectId);
        if (!$subject) return 0;

        $relatedSubjects = $this->getRelatedSubjects($subject);
        $score = 0;

        foreach ($relatedSubjects as $relatedSubject) {
            if ($teacher->subjects()->where('id', $relatedSubject->id)->exists()) {
                $score += Constants::get('scoring.workload_balance_score', 30); // Partial compatibility for related subjects
            }
        }

        return min($score, Constants::get('scoring.subject_compatibility_score', 60)); // Cap at 60 for related subjects
    }

    /**
     * Get related subjects based on subject category or department
     */
    private function getRelatedSubjects(Subject $subject): Collection
    {
        // Use same class as a proxy for relatedness in absence of category/department fields
        return Subject::where('class_id', $subject->class_id)
            ->where('id', '!=', $subject->id)
            ->get();
    }

    /**
     * Calculate class familiarity score
     */
    private function calculateClassFamiliarityScore(Teacher $teacher, $classId): int
    {
        $score = 0;
        
        // Direct class teaching experience (homeroom/class teacher)
        if ($teacher->classes()->where('id', $classId)->exists()) {
            $score += 50;
        }
        
        // Same section experience as a proxy for familiarity
        $class = ClassModel::find($classId);
        if ($class) {
            $sameSectionClasses = $teacher->classes()
                ->where('section', $class->section)
                ->count();
            $score += min($sameSectionClasses * 10, 30);
        }

        return min($score, 50);
    }

    /**
     * Calculate workload score (higher score for less busy teachers)
     */
    private function calculateWorkloadScore(Teacher $teacher, $date): int
    {
        $todaySubstitutions = $this->getTodaySubstitutionCount($teacher, $date);
        $maxSubstitutions = $this->getMaxSubstitutionsPerDay($teacher);
        
        // Calculate workload percentage
        $workloadPercentage = $maxSubstitutions > 0 ? ($todaySubstitutions / $maxSubstitutions) * 100 : 0;
        
        // Higher score for lower workload
        if ($workloadPercentage <= 25) return 30;
        if ($workloadPercentage <= 50) return 20;
        if ($workloadPercentage <= 75) return 10;
        return 5;
    }

    /**
     * Calculate experience score
     */
    private function calculateExperienceScore(Teacher $teacher): int
    {
        $score = 0;
        
        // Teaching experience
        $experience = $teacher->experience_years ?? 0;
        $score += min($experience * 2, 25);
        
        // Substitution experience
        $substitutionCount = TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
            ->where('status', 'completed')
            ->count();
        $score += min($substitutionCount, 15);

        return min($score, 40);
    }

    /**
     * Calculate availability preference score
     */
    private function calculateAvailabilityPreferenceScore(Teacher $teacher, $date): int
    {
        $availability = TeacherAvailability::where('teacher_id', $teacher->id)
            ->where('date', $date)
            ->first();

        if (!$availability) return 10; // Default score

        $score = 0;
        
        if ($availability->can_substitute) $score += 10;
        if ($availability->prefers_substitution) $score += 5;
        if ($availability->emergency_available) $score += 5;

        return min($score, 20);
    }

    /**
     * Calculate recent performance score
     */
    private function calculateRecentPerformanceScore(Teacher $teacher): int
    {
        $recentRating = TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
            ->where('substitution_date', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('rating')
            ->avg('rating');

        if (!$recentRating) return 15; // Default score

        return min($recentRating * 6, 30); // Scale 1-5 rating to 0-30 points
    }

    /**
     * Enhanced workload balance checking
     */
    private function checkWorkloadBalance(Teacher $teacher, $date, $options = []): bool
    {
        $todayCount = $this->getTodaySubstitutionCount($teacher, $date);
        $maxAllowed = $this->getMaxSubstitutionsPerDay($teacher);
        
        // Basic limit check
        if ($todayCount >= $maxAllowed) {
            return false;
        }

        // Emergency override
        if (isset($options['is_emergency']) && $options['is_emergency']) {
            return $todayCount < ($maxAllowed + 1); // Allow one extra for emergencies
        }

        // Weekly workload check
        $weeklyCount = $this->getWeeklySubstitutionCount($teacher, $date);
        $maxWeekly = $this->getMaxSubstitutionsPerWeek($teacher);
        
        return $weeklyCount < $maxWeekly;
    }

    /**
     * Check teacher preferences and constraints
     */
    private function checkTeacherPreferences(Teacher $teacher, $date, $startTime, $endTime, $options = []): bool
    {
        $availability = TeacherAvailability::where('teacher_id', $teacher->id)
            ->where('date', $date)
            ->first();

        if (!$availability) return true; // No specific preferences set

        // Check time preferences
        if ($availability->preferred_start_time && $startTime < $availability->preferred_start_time) {
            return false;
        }

        if ($availability->preferred_end_time && $endTime > $availability->preferred_end_time) {
            return false;
        }

        // Check subject preferences
        if (isset($options['subject_id']) && $availability->excluded_subjects) {
            $excludedSubjects = json_decode($availability->excluded_subjects, true) ?? [];
            if (in_array($options['subject_id'], $excludedSubjects)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if teacher has subject compatibility
     */
    private function hasSubjectCompatibility(Teacher $teacher, $subjectId): bool
    {
        return $teacher->subjects()->where('id', $subjectId)->exists();
    }

    /**
     * Check if teacher has related subject expertise
     */
    private function hasRelatedSubjectExpertise(Teacher $teacher, $subjectId): bool
    {
        $subject = Subject::find($subjectId);
        if (!$subject) return false;

        // Related expertise: teaches any subject in the same class
        return $teacher->subjects()
            ->where('class_id', $subject->class_id)
            ->exists();
    }

    /**
     * Get weekly substitution count
     */
    private function getWeeklySubstitutionCount(Teacher $teacher, $date): int
    {
        $startOfWeek = Carbon::parse($date)->startOfWeek();
        $endOfWeek = Carbon::parse($date)->endOfWeek();

        return TeacherSubstitution::where('substitute_teacher_id', $teacher->id)
            ->whereBetween('substitution_date', [$startOfWeek, $endOfWeek])
            ->whereIn('status', ['confirmed', 'assigned', 'completed'])
            ->count();
    }

    /**
     * Get maximum substitutions per week for teacher
     */
    private function getMaxSubstitutionsPerWeek(Teacher $teacher): int
    {
        return $teacher->max_weekly_substitutions ?? 10; // Default weekly limit
    }

    /**
     * Enhanced substitute teacher finder with intelligent matching and fallback strategies
     */
    public function findBestSubstituteEnhanced($date, $startTime, $endTime, $options = []): array
    {
        $result = [
            'primary_substitute' => null,
            'backup_substitutes' => [],
            'emergency_options' => [],
            'matching_strategy' => null,
            'confidence_score' => 0,
            'recommendations' => []
        ];

        // Strategy 1: Perfect Match (Subject + Class expertise)
        if (isset($options['subject_id']) && isset($options['class_id'])) {
            $perfectMatch = $this->findPerfectMatch($date, $startTime, $endTime, $options);
            if ($perfectMatch) {
                $result['primary_substitute'] = $perfectMatch;
                $result['matching_strategy'] = 'perfect_match';
                $result['confidence_score'] = 95;
                return $result;
            }
        }

        // Strategy 2: Subject Expert Match
        if (isset($options['subject_id'])) {
            $subjectExpert = $this->findSubjectExpert($date, $startTime, $endTime, $options);
            if ($subjectExpert) {
                $result['primary_substitute'] = $subjectExpert;
                $result['matching_strategy'] = 'subject_expert';
                $result['confidence_score'] = 85;
            }
        }

        // Strategy 3: Class Familiar Match
        if (!$result['primary_substitute'] && isset($options['class_id'])) {
            $classFamiliar = $this->findClassFamiliarTeacher($date, $startTime, $endTime, $options);
            if ($classFamiliar) {
                $result['primary_substitute'] = $classFamiliar;
                $result['matching_strategy'] = 'class_familiar';
                $result['confidence_score'] = 75;
            }
        }

        // Strategy 4: Best Available (General compatibility)
        if (!$result['primary_substitute']) {
            $bestAvailable = $this->findBestAvailableTeacher($date, $startTime, $endTime, $options);
            if ($bestAvailable) {
                $result['primary_substitute'] = $bestAvailable;
                $result['matching_strategy'] = 'best_available';
                $result['confidence_score'] = 65;
            }
        }

        // Find backup options
        $result['backup_substitutes'] = $this->findBackupSubstitutes($date, $startTime, $endTime, $options, $result['primary_substitute']);

        // Emergency options (if enabled)
        if (isset($options['include_emergency']) && $options['include_emergency']) {
            $result['emergency_options'] = $this->findEmergencyOptions($date, $startTime, $endTime, $options);
        }

        // Generate recommendations
        $result['recommendations'] = $this->generateSubstitutionRecommendations($result, $options);

        return $result;
    }

    /**
     * Find perfect match (subject + class expertise)
     */
    private function findPerfectMatch($date, $startTime, $endTime, $options): ?Teacher
    {
        $enhancedOptions = array_merge($options, ['require_perfect_match' => true]);
        $freeTeachers = $this->findFreeTeachers($date, $startTime, $endTime, null, $enhancedOptions);

        return $freeTeachers->filter(function ($teacher) use ($options) {
            return $this->hasSubjectCompatibility($teacher, $options['subject_id']) &&
                   $teacher->classes()->where('id', $options['class_id'])->exists();
        })->sortByDesc('compatibility_score')->first();
    }

    /**
     * Find subject expert
     */
    private function findSubjectExpert($date, $startTime, $endTime, $options): ?Teacher
    {
        $freeTeachers = $this->findFreeTeachers($date, $startTime, $endTime, null, $options);

        return $freeTeachers->filter(function ($teacher) use ($options) {
            return $this->hasSubjectCompatibility($teacher, $options['subject_id']);
        })->sortByDesc('compatibility_score')->first();
    }

    /**
     * Find class familiar teacher
     */
    private function findClassFamiliarTeacher($date, $startTime, $endTime, $options): ?Teacher
    {
        $freeTeachers = $this->findFreeTeachers($date, $startTime, $endTime, null, $options);

        return $freeTeachers->filter(function ($teacher) use ($options) {
            return $teacher->classes()->where('id', $options['class_id'])->exists();
        })->sortByDesc('compatibility_score')->first();
    }

    /**
     * Find best available teacher (general compatibility)
     */
    private function findBestAvailableTeacher($date, $startTime, $endTime, $options): ?Teacher
    {
        $freeTeachers = $this->findFreeTeachers($date, $startTime, $endTime, null, $options);
        return $freeTeachers->sortByDesc('compatibility_score')->first();
    }

    /**
     * Find backup substitute options
     */
    private function findBackupSubstitutes($date, $startTime, $endTime, $options, $primarySubstitute): Collection
    {
        $freeTeachers = $this->findFreeTeachers($date, $startTime, $endTime, null, $options);
        
        return $freeTeachers->filter(function ($teacher) use ($primarySubstitute) {
            return !$primarySubstitute || $teacher->id !== $primarySubstitute->id;
        })->sortByDesc('compatibility_score')->take(3);
    }

    /**
     * Find emergency substitution options
     */
    private function findEmergencyOptions($date, $startTime, $endTime, $options): Collection
    {
        $emergencyOptions = array_merge($options, ['is_emergency' => true]);
        
        // Relax some constraints for emergency situations
        $teachers = Teacher::where('is_active', true)
            ->where('can_substitute', true)
            ->with(['subjects', 'classes', 'user'])
            ->get();

        $emergencyTeachers = collect();

        foreach ($teachers as $teacher) {
            // More lenient availability check for emergencies
            if ($this->isEmergencyAvailable($teacher, $date, $startTime, $endTime, $emergencyOptions)) {
                $teacher->compatibility_score = $this->calculateCompatibilityScore(
                    $teacher, 
                    $options['subject_id'] ?? null, 
                    $options['class_id'] ?? null,
                    $date
                );
                $emergencyTeachers->push($teacher);
            }
        }

        return $emergencyTeachers->sortByDesc('compatibility_score')->take(2);
    }

    /**
     * Check emergency availability (more lenient)
     */
    private function isEmergencyAvailable(Teacher $teacher, $date, $startTime, $endTime, $options): bool
    {
        // Skip leave check for emergency (might be able to return)
        // Skip some preference checks
        
        // Only check critical conflicts
        if ($this->hasConflictingSubstitutions($teacher, $date, $startTime, $endTime)) {
            return false;
        }

        // Allow one extra substitution for emergencies
        $todayCount = $this->getTodaySubstitutionCount($teacher, $date);
        $maxAllowed = $this->getMaxSubstitutionsPerDay($teacher);
        
        return $todayCount <= $maxAllowed; // Allow one extra
    }

    /**
     * Generate substitution recommendations
     */
    private function generateSubstitutionRecommendations($result, $options): array
    {
        $recommendations = [];

        if (!$result['primary_substitute']) {
            $recommendations[] = [
                'type' => 'no_substitute_found',
                'message' => 'No suitable substitute found. Consider adjusting requirements or scheduling for later.',
                'suggestions' => [
                    'Expand time window',
                    'Consider related subject teachers',
                    'Check teacher availability preferences',
                    'Consider emergency protocols'
                ]
            ];
            return $recommendations;
        }

        // Confidence-based recommendations
        if ($result['confidence_score'] < 70) {
            $recommendations[] = [
                'type' => 'low_confidence',
                'message' => 'Substitute match has lower confidence. Consider additional preparation.',
                'suggestions' => [
                    'Provide detailed lesson plans',
                    'Brief substitute on class specifics',
                    'Arrange peer support if possible'
                ]
            ];
        }

        // Subject mismatch recommendations
        if (isset($options['subject_id']) && !$this->hasSubjectCompatibility($result['primary_substitute'], $options['subject_id'])) {
            $recommendations[] = [
                'type' => 'subject_mismatch',
                'message' => 'Substitute is not a subject expert. Provide comprehensive materials.',
                'suggestions' => [
                    'Prepare detailed lesson plans',
                    'Provide subject-specific resources',
                    'Consider rescheduling if possible',
                    'Arrange subject expert consultation'
                ]
            ];
        }

        // Workload recommendations
        $workloadPercentage = $this->getTeacherWorkloadPercentage($result['primary_substitute'], $options['date'] ?? Carbon::today());
        if ($workloadPercentage > 75) {
            $recommendations[] = [
                'type' => 'high_workload',
                'message' => 'Substitute has high workload today. Monitor for fatigue.',
                'suggestions' => [
                    'Keep lesson plans simple',
                    'Provide additional support',
                    'Consider shorter sessions if possible'
                ]
            ];
        }

        return $recommendations;
    }

    /**
     * Get teacher workload percentage for the day
     */
    private function getTeacherWorkloadPercentage(Teacher $teacher, $date): float
    {
        $todayCount = $this->getTodaySubstitutionCount($teacher, $date);
        $maxAllowed = $this->getMaxSubstitutionsPerDay($teacher);
        
        return $maxAllowed > 0 ? ($todayCount / $maxAllowed) * 100 : 0;
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
}