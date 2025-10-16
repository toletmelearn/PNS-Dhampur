<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEnrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'school_id',
        'class_name',
        'section',
        'roll_number',
        'admission_number',
        'academic_year',
        'enrollment_date',
        'status',
        'previous_class',
        'previous_school',
        'transfer_certificate_number',
        'enrolled_by',
        'promoted_from',
        'promotion_date',
        'fees_applicable',
        'scholarship_applicable',
        'scholarship_percentage',
        'transport_required',
        'hostel_required',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'promotion_date' => 'date',
        'fees_applicable' => 'decimal:2',
        'scholarship_percentage' => 'decimal:2',
        'transport_required' => 'boolean',
        'hostel_required' => 'boolean',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'enrollment_date',
        'promotion_date',
        'deleted_at'
    ];

    // Enrollment status
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_TRANSFERRED = 'transferred';
    const STATUS_GRADUATED = 'graduated';
    const STATUS_DROPPED = 'dropped';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Get the student for this enrollment
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the school for this enrollment
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user who enrolled this student
     */
    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    /**
     * Get full class name with section
     */
    public function getFullClassNameAttribute(): string
    {
        if ($this->section) {
            return $this->class_name . ' - ' . $this->section;
        }
        
        return $this->class_name;
    }

    /**
     * Get enrollment display name
     */
    public function getDisplayNameAttribute(): string
    {
        $parts = [$this->full_class_name];
        
        if ($this->roll_number) {
            $parts[] = "Roll: {$this->roll_number}";
        }
        
        if ($this->admission_number) {
            $parts[] = "Adm: {$this->admission_number}";
        }
        
        return implode(' | ', $parts);
    }

    /**
     * Check if enrollment is currently active
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if enrollment is for current academic year
     */
    public function isCurrentAcademicYear(): bool
    {
        $currentYear = $this->getCurrentAcademicYear();
        return $this->academic_year === $currentYear;
    }

    /**
     * Get current academic year
     */
    protected function getCurrentAcademicYear(): string
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Assuming academic year starts in April
        if ($currentMonth >= 4) {
            return $currentYear . '-' . ($currentYear + 1);
        } else {
            return ($currentYear - 1) . '-' . $currentYear;
        }
    }

    /**
     * Check if student has scholarship
     */
    public function hasScholarship(): bool
    {
        return $this->scholarship_applicable && $this->scholarship_percentage > 0;
    }

    /**
     * Get scholarship amount
     */
    public function getScholarshipAmount(): float
    {
        if (!$this->hasScholarship()) {
            return 0;
        }
        
        return ($this->fees_applicable * $this->scholarship_percentage) / 100;
    }

    /**
     * Get net fees after scholarship
     */
    public function getNetFees(): float
    {
        return $this->fees_applicable - $this->getScholarshipAmount();
    }

    /**
     * Get enrollment duration in days
     */
    public function getEnrollmentDurationInDays(): int
    {
        return $this->enrollment_date->diffInDays(now());
    }

    /**
     * Check if student was promoted
     */
    public function wasPromoted(): bool
    {
        return !empty($this->promoted_from) && !empty($this->promotion_date);
    }

    /**
     * Check if student was transferred
     */
    public function wasTransferred(): bool
    {
        return !empty($this->previous_school) && !empty($this->transfer_certificate_number);
    }

    /**
     * Scope: Active enrollments only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Enrollments by student
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope: Enrollments by school
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope: Enrollments by class
     */
    public function scopeByClass($query, string $className, string $section = null)
    {
        $query = $query->where('class_name', $className);
        
        if ($section) {
            $query->where('section', $section);
        }
        
        return $query;
    }

    /**
     * Scope: Enrollments by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Enrollments by academic year
     */
    public function scopeByAcademicYear($query, string $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Scope: Current academic year enrollments
     */
    public function scopeCurrentAcademicYear($query)
    {
        $currentYear = $this->getCurrentAcademicYear();
        return $query->where('academic_year', $currentYear);
    }

    /**
     * Scope: Students with scholarship
     */
    public function scopeWithScholarship($query)
    {
        return $query->where('scholarship_applicable', true)
                    ->where('scholarship_percentage', '>', 0);
    }

    /**
     * Scope: Students requiring transport
     */
    public function scopeRequiringTransport($query)
    {
        return $query->where('transport_required', true);
    }

    /**
     * Scope: Students requiring hostel
     */
    public function scopeRequiringHostel($query)
    {
        return $query->where('hostel_required', true);
    }

    /**
     * Scope: Promoted students
     */
    public function scopePromoted($query)
    {
        return $query->whereNotNull('promoted_from')
                    ->whereNotNull('promotion_date');
    }

    /**
     * Scope: Transferred students
     */
    public function scopeTransferred($query)
    {
        return $query->whereNotNull('previous_school')
                    ->whereNotNull('transfer_certificate_number');
    }

    /**
     * Get student's current enrollment
     */
    public static function getCurrentEnrollment($studentId, $schoolId = null)
    {
        $query = self::where('student_id', $studentId)
                    ->active()
                    ->currentAcademicYear();
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        return $query->first();
    }

    /**
     * Get students in a specific class
     */
    public static function getClassStudents(string $className, string $section = null, $schoolId = null, $academicYear = null): array
    {
        $query = self::where('class_name', $className)
                    ->active();
        
        if ($section) {
            $query->where('section', $section);
        }
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        } else {
            $query->currentAcademicYear();
        }
        
        return $query->with(['student', 'student.profile'])
                    ->orderBy('roll_number')
                    ->get()
                    ->map(function($enrollment) {
                        return [
                            'enrollment_id' => $enrollment->id,
                            'student_id' => $enrollment->student_id,
                            'student_name' => $enrollment->student->name,
                            'roll_number' => $enrollment->roll_number,
                            'admission_number' => $enrollment->admission_number,
                            'full_class_name' => $enrollment->full_class_name,
                            'status' => $enrollment->status,
                            'has_scholarship' => $enrollment->hasScholarship(),
                            'transport_required' => $enrollment->transport_required,
                            'hostel_required' => $enrollment->hostel_required,
                        ];
                    })
                    ->toArray();
    }

    /**
     * Check if student is enrolled in specific class
     */
    public static function isStudentInClass($studentId, string $className, string $section = null, $schoolId = null): bool
    {
        $query = self::where('student_id', $studentId)
                    ->where('class_name', $className)
                    ->active()
                    ->currentAcademicYear();
        
        if ($section) {
            $query->where('section', $section);
        }
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        return $query->exists();
    }

    /**
     * Get class statistics
     */
    public static function getClassStatistics(string $className, string $section = null, $schoolId = null, $academicYear = null): array
    {
        $query = self::where('class_name', $className)
                    ->where('is_active', true);
        
        if ($section) {
            $query->where('section', $section);
        }
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        } else {
            $query->currentAcademicYear();
        }
        
        $enrollments = $query->get();
        
        return [
            'total_students' => $enrollments->count(),
            'active_students' => $enrollments->where('status', self::STATUS_ACTIVE)->count(),
            'students_with_scholarship' => $enrollments->where('scholarship_applicable', true)->count(),
            'students_requiring_transport' => $enrollments->where('transport_required', true)->count(),
            'students_requiring_hostel' => $enrollments->where('hostel_required', true)->count(),
            'promoted_students' => $enrollments->whereNotNull('promoted_from')->count(),
            'transferred_students' => $enrollments->whereNotNull('previous_school')->count(),
            'average_scholarship_percentage' => $enrollments->where('scholarship_applicable', true)->avg('scholarship_percentage') ?? 0,
            'total_fees_applicable' => $enrollments->sum('fees_applicable'),
            'total_scholarship_amount' => $enrollments->sum(function($enrollment) {
                return $enrollment->getScholarshipAmount();
            }),
        ];
    }

    /**
     * Enroll student in class
     */
    public static function enrollStudent(array $data): self
    {
        // Set default values
        $data['enrollment_date'] = $data['enrollment_date'] ?? now()->toDateString();
        $data['academic_year'] = $data['academic_year'] ?? (new self())->getCurrentAcademicYear();
        $data['status'] = $data['status'] ?? self::STATUS_ACTIVE;
        $data['is_active'] = $data['is_active'] ?? true;
        
        // Generate admission number if not provided
        if (empty($data['admission_number'])) {
            $data['admission_number'] = self::generateAdmissionNumber($data['school_id']);
        }
        
        return self::create($data);
    }

    /**
     * Generate unique admission number
     */
    protected static function generateAdmissionNumber($schoolId): string
    {
        $year = now()->year;
        $prefix = "ADM{$year}";
        
        $lastNumber = self::where('school_id', $schoolId)
                         ->where('admission_number', 'like', "{$prefix}%")
                         ->orderBy('admission_number', 'desc')
                         ->value('admission_number');
        
        if ($lastNumber) {
            $number = intval(substr($lastNumber, -4)) + 1;
        } else {
            $number = 1;
        }
        
        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Transfer student
     */
    public function transferStudent(string $reason = null): void
    {
        $this->status = self::STATUS_TRANSFERRED;
        $this->is_active = false;
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Transferred: {$reason}";
        }
        
        $this->save();
    }

    /**
     * Promote student to next class
     */
    public function promoteStudent(string $newClass, string $newSection = null, array $additionalData = []): self
    {
        // Create new enrollment for next academic year
        $nextYear = $this->getNextAcademicYear();
        
        $newEnrollmentData = array_merge([
            'student_id' => $this->student_id,
            'school_id' => $this->school_id,
            'class_name' => $newClass,
            'section' => $newSection,
            'academic_year' => $nextYear,
            'promoted_from' => $this->full_class_name,
            'promotion_date' => now()->toDateString(),
            'enrolled_by' => auth()->id(),
            'fees_applicable' => $this->fees_applicable,
            'scholarship_applicable' => $this->scholarship_applicable,
            'scholarship_percentage' => $this->scholarship_percentage,
            'transport_required' => $this->transport_required,
            'hostel_required' => $this->hostel_required,
        ], $additionalData);
        
        return self::enrollStudent($newEnrollmentData);
    }

    /**
     * Get next academic year
     */
    protected function getNextAcademicYear(): string
    {
        $parts = explode('-', $this->academic_year);
        $startYear = intval($parts[0]) + 1;
        $endYear = intval($parts[1]) + 1;
        
        return $startYear . '-' . $endYear;
    }

    /**
     * Get available enrollment statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_TRANSFERRED => 'Transferred',
            self::STATUS_GRADUATED => 'Graduated',
            self::STATUS_DROPPED => 'Dropped',
            self::STATUS_SUSPENDED => 'Suspended',
        ];
    }
}