<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'admission_no',
        'name',
        'father_name',
        'mother_name',
        'dob',
        'aadhaar',
        'class_id',
        'documents',
        'documents_verified_data',
        'verification_status',
        'status',
        'verified',
        'meta',
    ];

    protected $casts = [
        'documents' => 'array',
        'documents_verified_data' => 'array',
        'meta' => 'array',
        'dob' => 'date',
    ];

    // relationships
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function histories()
    {
        return $this->hasMany(\App\Models\StudentHistory::class);
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function srRegisters()
    {
        return $this->hasMany(SRRegister::class);
    }

    public function documentVerifications()
    {
        return $this->hasMany(DocumentVerification::class);
    }

    public function studentVerifications()
    {
        return $this->hasMany(StudentVerification::class);
    }

    public function promotionRecords()
    {
        return $this->hasMany(\App\Models\PromotionRecord::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(VerificationAuditLog::class);
    }

    public function transferCertificates()
    {
        return $this->hasMany(\App\Models\TransferCertificate::class);
    }

    // helper to get public URL for stored file
    public function documentUrl($key)
    {
        if (! $this->documents || ! isset($this->documents[$key])) {
            return null;
        }
        return Storage::url($this->documents[$key]);
    }

    /**
     * Cache Methods for Performance Optimization
     */
    
    /**
     * Get cached students by class
     */
    public static function getCachedByClass($classId, $ttl = 3600)
    {
        $cacheKey = "students_class_{$classId}";
        
        return Cache::remember($cacheKey, $ttl, function () use ($classId) {
            return self::with(['class', 'user'])
                ->where('class_id', $classId)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get cached student attendance percentage
     */
    public function getCachedAttendancePercentage($academicYear = null, $ttl = 1800)
    {
        $academicYear = $academicYear ?? date('Y');
        $cacheKey = "student_attendance_{$this->id}_{$academicYear}";
        
        return Cache::remember($cacheKey, $ttl, function () use ($academicYear) {
            $totalDays = $this->attendance()
                ->where('academic_year', $academicYear)
                ->whereNotIn('status', ['holiday'])
                ->count();
                
            if ($totalDays == 0) return 0;
            
            $presentDays = $this->attendance()
                ->where('academic_year', $academicYear)
                ->whereIn('status', ['present', 'late'])
                ->count();
                
            return round(($presentDays / $totalDays) * 100, 2);
        });
    }

    /**
     * Get cached active students count
     */
    public static function getCachedActiveCount($ttl = 3600)
    {
        return Cache::remember('active_students_count', $ttl, function () {
            return self::where('status', 'active')->count();
        });
    }

    /**
     * Get cached students with low attendance
     */
    public static function getCachedLowAttendanceStudents($threshold = 75, $academicYear = null, $ttl = 1800)
    {
        $academicYear = $academicYear ?? date('Y');
        $cacheKey = "low_attendance_students_{$threshold}_{$academicYear}";
        
        return Cache::remember($cacheKey, $ttl, function () use ($threshold, $academicYear) {
            return self::with(['class', 'attendance'])
                ->where('status', 'active')
                ->get()
                ->filter(function ($student) use ($threshold, $academicYear) {
                    $attendancePercentage = $student->getCachedAttendancePercentage($academicYear, 0); // No cache for calculation
                    return $attendancePercentage < $threshold && $attendancePercentage > 0;
                })
                ->sortBy('name')
                ->values();
        });
    }

    /**
     * Get cached class-wise student statistics
     */
    public static function getCachedClassWiseStats($ttl = 3600)
    {
        return Cache::remember('class_wise_student_stats', $ttl, function () {
            return self::selectRaw('class_id, COUNT(*) as total_students, 
                                   SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_students')
                ->with('class:id,name')
                ->groupBy('class_id')
                ->get()
                ->keyBy('class_id');
        });
    }

    /**
     * Clear student-related cache
     */
    public static function clearStudentCache($studentId = null, $classId = null)
    {
        $patterns = [
            'active_students_count',
            'class_wise_student_stats',
            'low_attendance_students_*',
        ];

        if ($studentId) {
            $patterns[] = "student_attendance_{$studentId}_*";
        }

        if ($classId) {
            $patterns[] = "students_class_{$classId}";
        }

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // For patterns with wildcards, we'd need to implement cache tag-based clearing
                // For now, we'll clear specific known keys
                Cache::forget(str_replace('*', date('Y'), $pattern));
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Model events to clear cache when data changes
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($student) {
            self::clearStudentCache($student->id, $student->class_id);
        });

        static::deleted(function ($student) {
            self::clearStudentCache($student->id, $student->class_id);
        });
    }
}
