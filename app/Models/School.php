<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'board',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'email',
        'website',
        'established_year',
        'principal_id',
        'logo',
        'description',
        'facilities',
        'academic_year_start',
        'academic_year_end',
        'working_days',
        'school_hours_start',
        'school_hours_end',
        'total_students',
        'total_teachers',
        'total_staff',
        'affiliation_number',
        'recognition_status',
        'accreditation',
        'settings',
        'is_active'
    ];

    protected $casts = [
        'established_year' => 'integer',
        'facilities' => 'array',
        'academic_year_start' => 'date',
        'academic_year_end' => 'date',
        'working_days' => 'array',
        'school_hours_start' => 'datetime:H:i',
        'school_hours_end' => 'datetime:H:i',
        'total_students' => 'integer',
        'total_teachers' => 'integer',
        'total_staff' => 'integer',
        'settings' => 'array',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'academic_year_start',
        'academic_year_end',
        'deleted_at'
    ];

    // School types
    const TYPE_PRIMARY = 'primary';
    const TYPE_SECONDARY = 'secondary';
    const TYPE_HIGHER_SECONDARY = 'higher_secondary';
    const TYPE_SENIOR_SECONDARY = 'senior_secondary';
    const TYPE_COMPOSITE = 'composite';

    // Board types
    const BOARD_CBSE = 'cbse';
    const BOARD_ICSE = 'icse';
    const BOARD_STATE = 'state';
    const BOARD_IB = 'ib';
    const BOARD_IGCSE = 'igcse';

    // Recognition status
    const RECOGNITION_RECOGNIZED = 'recognized';
    const RECOGNITION_PROVISIONAL = 'provisional';
    const RECOGNITION_PENDING = 'pending';
    const RECOGNITION_SUSPENDED = 'suspended';

    /**
     * Get the principal of this school
     */
    public function principal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'principal_id');
    }

    /**
     * Get all users associated with this school
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all user profiles associated with this school
     */
    public function userProfiles(): HasMany
    {
        return $this->hasMany(UserProfile::class);
    }

    /**
     * Get all teachers in this school
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('roles', function($query) {
            $query->where('name', 'teacher');
        });
    }

    /**
     * Get all students in this school
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('roles', function($query) {
            $query->where('name', 'student');
        });
    }

    /**
     * Get all parents associated with this school
     */
    public function parents(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('roles', function($query) {
            $query->where('name', 'parent');
        });
    }

    /**
     * Get all staff members in this school
     */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('roles', function($query) {
            $query->whereIn('name', ['admin', 'principal', 'teacher', 'staff']);
        });
    }

    /**
     * Get teacher assignments for this school
     */
    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /**
     * Get student enrollments for this school
     */
    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get school logo URL
     */
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        
        return asset('images/default-school-logo.png');
    }

    /**
     * Get current academic year
     */
    public function getCurrentAcademicYear(): string
    {
        if ($this->academic_year_start && $this->academic_year_end) {
            return $this->academic_year_start->format('Y') . '-' . $this->academic_year_end->format('Y');
        }
        
        return date('Y') . '-' . (date('Y') + 1);
    }

    /**
     * Check if school is currently active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get working days list
     */
    public function getWorkingDaysList(): array
    {
        return $this->working_days ?? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    }

    /**
     * Check if a day is a working day
     */
    public function isWorkingDay(string $day): bool
    {
        return in_array(strtolower($day), $this->getWorkingDaysList());
    }

    /**
     * Get facilities list
     */
    public function getFacilitiesList(): array
    {
        return $this->facilities ?? [];
    }

    /**
     * Check if school has a facility
     */
    public function hasFacility(string $facility): bool
    {
        return in_array($facility, $this->getFacilitiesList());
    }

    /**
     * Add facility
     */
    public function addFacility(string $facility): void
    {
        $facilities = $this->facilities ?? [];
        if (!in_array($facility, $facilities)) {
            $facilities[] = $facility;
            $this->facilities = $facilities;
            $this->save();
        }
    }

    /**
     * Remove facility
     */
    public function removeFacility(string $facility): void
    {
        $facilities = $this->facilities ?? [];
        $facilities = array_values(array_filter($facilities, fn($f) => $f !== $facility));
        $this->facilities = $facilities;
        $this->save();
    }

    /**
     * Get school setting
     */
    public function getSetting(string $key, $default = null)
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? $default;
    }

    /**
     * Set school setting
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
        $this->save();
    }

    /**
     * Get school statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_students' => $this->students()->count(),
            'total_teachers' => $this->teachers()->count(),
            'total_parents' => $this->parents()->count(),
            'total_staff' => $this->staff()->count(),
            'active_users' => $this->users()->where('is_active', true)->count(),
            'teacher_student_ratio' => $this->getTeacherStudentRatio(),
        ];
    }

    /**
     * Get teacher to student ratio
     */
    public function getTeacherStudentRatio(): string
    {
        $teacherCount = $this->teachers()->count();
        $studentCount = $this->students()->count();
        
        if ($teacherCount === 0) {
            return '0:0';
        }
        
        $ratio = round($studentCount / $teacherCount, 1);
        return "1:{$ratio}";
    }

    /**
     * Update student and teacher counts
     */
    public function updateCounts(): void
    {
        $this->total_students = $this->students()->count();
        $this->total_teachers = $this->teachers()->count();
        $this->total_staff = $this->staff()->count();
        $this->save();
    }

    /**
     * Scope: Active schools only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Schools by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Schools by board
     */
    public function scopeByBoard($query, string $board)
    {
        return $query->where('board', $board);
    }

    /**
     * Scope: Schools by recognition status
     */
    public function scopeByRecognitionStatus($query, string $status)
    {
        return $query->where('recognition_status', $status);
    }

    /**
     * Scope: Search schools by name or code
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%");
        });
    }

    /**
     * Get default school settings
     */
    public static function getDefaultSettings(): array
    {
        return [
            'attendance_marking_time_limit' => 30, // minutes
            'late_arrival_threshold' => 15, // minutes
            'early_departure_threshold' => 30, // minutes
            'minimum_attendance_percentage' => 75,
            'academic_year_start_month' => 4, // April
            'academic_year_end_month' => 3, // March
            'grading_system' => 'percentage',
            'maximum_marks' => 100,
            'passing_marks' => 35,
            'report_card_template' => 'default',
            'notification_settings' => [
                'attendance_alerts' => true,
                'grade_updates' => true,
                'fee_reminders' => true,
                'event_notifications' => true,
            ],
            'security_settings' => [
                'password_expiry_days' => 90,
                'max_login_attempts' => 5,
                'session_timeout_minutes' => 60,
                'require_password_change_on_first_login' => true,
            ]
        ];
    }

    /**
     * Initialize school with default settings
     */
    public function initializeDefaults(): void
    {
        if (empty($this->settings)) {
            $this->settings = self::getDefaultSettings();
            $this->save();
        }

        if (empty($this->working_days)) {
            $this->working_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
            $this->save();
        }
    }

    /**
     * Get available school types
     */
    public static function getSchoolTypes(): array
    {
        return [
            self::TYPE_PRIMARY => 'Primary School',
            self::TYPE_SECONDARY => 'Secondary School',
            self::TYPE_HIGHER_SECONDARY => 'Higher Secondary School',
            self::TYPE_SENIOR_SECONDARY => 'Senior Secondary School',
            self::TYPE_COMPOSITE => 'Composite School',
        ];
    }

    /**
     * Get available boards
     */
    public static function getBoards(): array
    {
        return [
            self::BOARD_CBSE => 'CBSE',
            self::BOARD_ICSE => 'ICSE',
            self::BOARD_STATE => 'State Board',
            self::BOARD_IB => 'International Baccalaureate',
            self::BOARD_IGCSE => 'IGCSE',
        ];
    }

    /**
     * Get recognition statuses
     */
    public static function getRecognitionStatuses(): array
    {
        return [
            self::RECOGNITION_RECOGNIZED => 'Recognized',
            self::RECOGNITION_PROVISIONAL => 'Provisional',
            self::RECOGNITION_PENDING => 'Pending',
            self::RECOGNITION_SUSPENDED => 'Suspended',
        ];
    }
}