<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id','qualification','experience_years','salary','joining_date','documents','is_active','can_substitute'
    ];

    protected $casts = ['documents'=>'array','is_active'=>'boolean','can_substitute'=>'boolean'];

    public function user() { return $this->belongsTo(User::class); }
    public function classes() { return $this->hasMany(ClassModel::class, 'class_teacher_id'); }
    public function salaries() { return $this->hasMany(Salary::class); }
    public function syllabus() { return $this->hasMany(Syllabus::class); }
    
    // Substitution relationships
    public function substitutionRequests() { return $this->hasMany(TeacherSubstitution::class, 'original_teacher_id'); }
    public function substitutionAssignments() { return $this->hasMany(TeacherSubstitution::class, 'substitute_teacher_id'); }
    public function availability() { return $this->hasMany(TeacherAvailability::class); }
    public function absences() { return $this->hasMany(TeacherAbsence::class, 'teacher_id'); }
    
    // Document relationships
    public function teacherDocuments() { return $this->hasMany(TeacherDocument::class); }

    /**
     * Cache Methods for Performance Optimization
     */
    
    /**
     * Get cached active teachers list
     */
    public static function getCachedActiveTeachers($ttl = 3600)
    {
        return Cache::remember('active_teachers_list', $ttl, function () {
            return self::with(['user', 'classes'])
                ->whereHas('user', function ($query) {
                    $query->where('is_active', true);
                })
                ->orderBy('joining_date', 'desc')
                ->get();
        });
    }

    /**
     * Get cached teacher statistics
     */
    public static function getCachedTeacherStats($ttl = 3600)
    {
        return Cache::remember('teacher_statistics', $ttl, function () {
            return [
                'total_teachers' => self::count(),
                'active_teachers' => self::whereHas('user', function ($query) {
                    $query->where('is_active', true);
                })->count(),
                'average_experience' => self::avg('experience_years') ?? 0,
                'average_salary' => self::avg('salary') ?? 0,
                'recent_joinings' => self::where('joining_date', '>=', now()->subMonths(6))->count(),
            ];
        });
    }

    /**
     * Get cached available teachers for substitution
     */
    public static function getCachedAvailableTeachers($date = null, $timeSlot = null, $ttl = 1800)
    {
        $date = $date ?? now()->format('Y-m-d');
        $cacheKey = "available_teachers_{$date}_{$timeSlot}";
        
        return Cache::remember($cacheKey, $ttl, function () use ($date, $timeSlot) {
            return self::with(['user', 'availability'])
                ->whereHas('user', function ($query) {
                    $query->where('is_active', true);
                })
                ->whereDoesntHave('substitutionRequests', function ($query) use ($date) {
                    $query->whereDate('substitution_date', $date);
                })
                ->get()
                ->filter(function ($teacher) use ($date, $timeSlot) {
                    // Additional availability logic can be added here
                    return true;
                });
        });
    }

    /**
     * Get cached teacher performance metrics
     */
    public function getCachedPerformanceMetrics($academicYear = null, $ttl = 3600)
    {
        $academicYear = $academicYear ?? date('Y');
        $cacheKey = "teacher_performance_{$this->id}_{$academicYear}";
        
        return Cache::remember($cacheKey, $ttl, function () use ($academicYear) {
            return [
                'classes_taught' => $this->classes()->count(),
                'substitutions_completed' => $this->substitutionAssignments()
                    ->whereYear('substitution_date', $academicYear)
                    ->where('status', 'completed')
                    ->count(),
                'substitutions_requested' => $this->substitutionRequests()
                    ->whereYear('substitution_date', $academicYear)
                    ->count(),
                'reliability_score' => $this->calculateReliabilityScore($academicYear),
                'experience_years' => $this->experience_years,
                'subjects_taught' => $this->getSubjectsTaught(),
            ];
        });
    }

    /**
     * Get cached teacher workload
     */
    public function getCachedWorkload($ttl = 1800)
    {
        $cacheKey = "teacher_workload_{$this->id}";
        
        return Cache::remember($cacheKey, $ttl, function () {
            return [
                'total_classes' => $this->classes()->count(),
                'active_substitutions' => $this->substitutionAssignments()
                    ->where('status', 'active')
                    ->count(),
                'pending_requests' => $this->substitutionRequests()
                    ->where('status', 'pending')
                    ->count(),
                'workload_percentage' => $this->calculateWorkloadPercentage(),
            ];
        });
    }

    /**
     * Get cached teachers by department/subject
     */
    public static function getCachedBySubject($subject, $ttl = 3600)
    {
        $cacheKey = "teachers_subject_{$subject}";
        
        return Cache::remember($cacheKey, $ttl, function () use ($subject) {
            return self::with(['user', 'classes'])
                ->whereHas('user', function ($query) {
                    $query->where('is_active', true);
                })
                ->get()
                ->filter(function ($teacher) use ($subject) {
                    return in_array($subject, $teacher->getSubjectsTaught());
                });
        });
    }

    /**
     * Clear teacher-related cache
     */
    public static function clearTeacherCache($teacherId = null)
    {
        $patterns = [
            'active_teachers_list',
            'teacher_statistics',
            'available_teachers_*',
            'teachers_subject_*',
        ];

        if ($teacherId) {
            $patterns[] = "teacher_performance_{$teacherId}_*";
            $patterns[] = "teacher_workload_{$teacherId}";
        }

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // For patterns with wildcards, clear common variations
                if (str_contains($pattern, 'available_teachers_')) {
                    for ($i = 0; $i < 7; $i++) {
                        $date = now()->addDays($i)->format('Y-m-d');
                        Cache::forget("available_teachers_{$date}_morning");
                        Cache::forget("available_teachers_{$date}_afternoon");
                        Cache::forget("available_teachers_{$date}_");
                    }
                } elseif (str_contains($pattern, 'teacher_performance_') && $teacherId) {
                    Cache::forget("teacher_performance_{$teacherId}_" . date('Y'));
                }
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Helper methods for calculations
     */
    private function calculateReliabilityScore($academicYear)
    {
        $totalAssignments = $this->substitutionAssignments()
            ->whereYear('substitution_date', $academicYear)
            ->count();
            
        if ($totalAssignments == 0) return 100;
        
        $completedAssignments = $this->substitutionAssignments()
            ->whereYear('substitution_date', $academicYear)
            ->where('status', 'completed')
            ->count();
            
        return round(($completedAssignments / $totalAssignments) * 100, 2);
    }

    private function calculateWorkloadPercentage()
    {
        $maxClasses = 8; // Assuming max 8 classes per teacher
        $currentClasses = $this->classes()->count();
        $activeSubstitutions = $this->substitutionAssignments()
            ->where('status', 'active')
            ->count();
            
        return min(100, round((($currentClasses + $activeSubstitutions) / $maxClasses) * 100, 2));
    }

    // Add subjects relationship (a teacher can teach many subjects)
    public function subjects() { return $this->hasMany(Subject::class, 'teacher_id'); }

    private function getSubjectsTaught()
    {
        // This would typically come from a subjects relationship or metadata
        // For now, return a placeholder
        return ['Mathematics', 'Science']; // Replace with actual logic
    }

    /**
     * Model events to clear cache when data changes
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($teacher) {
            self::clearTeacherCache($teacher->id);
        });

        static::deleted(function ($teacher) {
            self::clearTeacherCache($teacher->id);
        });
    }

    // Experience/Portfolio relationships
    public function experience()
    {
        return $this->hasOne(TeacherExperience::class);
    }

    public function employmentHistories()
    {
        return $this->hasMany(EmploymentHistory::class);
    }

    public function certifications()
    {
        return $this->hasMany(Certification::class);
    }

    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'skill_teacher')
            ->withPivot(['proficiency_level', 'years_experience', 'verified', 'endorsements_count', 'notes'])
            ->withTimestamps();
    }
}
