<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'school_id',
        'employee_id',
        'student_id',
        'first_name',
        'last_name',
        'middle_name',
        'date_of_birth',
        'gender',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'profile_photo',
        'bio',
        'qualifications',
        'experience_years',
        'specializations',
        'subjects_taught',
        'classes_assigned',
        'joining_date',
        'leaving_date',
        'salary',
        'designation',
        'department',
        'blood_group',
        'medical_conditions',
        'allergies',
        'parent_occupation',
        'annual_income',
        'transport_required',
        'bus_route',
        'hostel_required',
        'room_number',
        'additional_info',
        'is_active'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'joining_date' => 'date',
        'leaving_date' => 'date',
        'qualifications' => 'array',
        'specializations' => 'array',
        'subjects_taught' => 'array',
        'classes_assigned' => 'array',
        'medical_conditions' => 'array',
        'allergies' => 'array',
        'additional_info' => 'array',
        'salary' => 'decimal:2',
        'annual_income' => 'decimal:2',
        'experience_years' => 'integer',
        'transport_required' => 'boolean',
        'hostel_required' => 'boolean',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'date_of_birth',
        'joining_date',
        'leaving_date',
        'deleted_at'
    ];

    // Gender options
    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';
    const GENDER_OTHER = 'other';

    // Blood group options
    const BLOOD_GROUPS = [
        'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'
    ];

    /**
     * Get the user that owns this profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the school this profile belongs to
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get full name
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name
        ]);
        
        return implode(' ', $parts);
    }

    /**
     * Get display name (first name + last name)
     */
    public function getDisplayNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get age from date of birth
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }
        
        return $this->date_of_birth->diffInYears(now());
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
     * Get profile photo URL
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo) {
            return asset('storage/' . $this->profile_photo);
        }
        
        // Return default avatar based on gender
        $defaultAvatar = match($this->gender) {
            self::GENDER_FEMALE => 'images/default-female-avatar.png',
            self::GENDER_MALE => 'images/default-male-avatar.png',
            default => 'images/default-avatar.png'
        };
        
        return asset($defaultAvatar);
    }

    /**
     * Check if profile is complete
     */
    public function isComplete(): bool
    {
        $requiredFields = [
            'first_name',
            'last_name',
            'date_of_birth',
            'gender',
            'phone',
            'address'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentage(): int
    {
        $allFields = [
            'first_name', 'last_name', 'date_of_birth', 'gender', 'phone',
            'address', 'city', 'state', 'postal_code', 'emergency_contact_name',
            'emergency_contact_phone', 'profile_photo', 'bio'
        ];
        
        $filledFields = 0;
        foreach ($allFields as $field) {
            if (!empty($this->$field)) {
                $filledFields++;
            }
        }
        
        return round(($filledFields / count($allFields)) * 100);
    }

    /**
     * Check if user is a teacher
     */
    public function isTeacher(): bool
    {
        return $this->user && $this->user->hasRole('teacher');
    }

    /**
     * Check if user is a student
     */
    public function isStudent(): bool
    {
        return $this->user && $this->user->hasRole('student');
    }

    /**
     * Check if user is a parent
     */
    public function isParent(): bool
    {
        return $this->user && $this->user->hasRole('parent');
    }

    /**
     * Get subjects taught (for teachers)
     */
    public function getSubjectsTaughtList(): array
    {
        return $this->subjects_taught ?? [];
    }

    /**
     * Get classes assigned (for teachers)
     */
    public function getClassesAssignedList(): array
    {
        return $this->classes_assigned ?? [];
    }

    /**
     * Get qualifications list
     */
    public function getQualificationsList(): array
    {
        return $this->qualifications ?? [];
    }

    /**
     * Get specializations list
     */
    public function getSpecializationsList(): array
    {
        return $this->specializations ?? [];
    }

    /**
     * Add qualification
     */
    public function addQualification(string $qualification): void
    {
        $qualifications = $this->qualifications ?? [];
        if (!in_array($qualification, $qualifications)) {
            $qualifications[] = $qualification;
            $this->qualifications = $qualifications;
            $this->save();
        }
    }

    /**
     * Remove qualification
     */
    public function removeQualification(string $qualification): void
    {
        $qualifications = $this->qualifications ?? [];
        $qualifications = array_values(array_filter($qualifications, fn($q) => $q !== $qualification));
        $this->qualifications = $qualifications;
        $this->save();
    }

    /**
     * Add subject taught
     */
    public function addSubjectTaught(string $subject): void
    {
        $subjects = $this->subjects_taught ?? [];
        if (!in_array($subject, $subjects)) {
            $subjects[] = $subject;
            $this->subjects_taught = $subjects;
            $this->save();
        }
    }

    /**
     * Remove subject taught
     */
    public function removeSubjectTaught(string $subject): void
    {
        $subjects = $this->subjects_taught ?? [];
        $subjects = array_values(array_filter($subjects, fn($s) => $s !== $subject));
        $this->subjects_taught = $subjects;
        $this->save();
    }

    /**
     * Add class assigned
     */
    public function addClassAssigned(string $class): void
    {
        $classes = $this->classes_assigned ?? [];
        if (!in_array($class, $classes)) {
            $classes[] = $class;
            $this->classes_assigned = $classes;
            $this->save();
        }
    }

    /**
     * Remove class assigned
     */
    public function removeClassAssigned(string $class): void
    {
        $classes = $this->classes_assigned ?? [];
        $classes = array_values(array_filter($classes, fn($c) => $c !== $class));
        $this->classes_assigned = $classes;
        $this->save();
    }

    /**
     * Scope: Active profiles only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Profiles by school
     */
    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope: Teacher profiles
     */
    public function scopeTeachers($query)
    {
        return $query->whereHas('user', function($q) {
            $q->whereHas('roles', function($roleQuery) {
                $roleQuery->where('name', 'teacher');
            });
        });
    }

    /**
     * Scope: Student profiles
     */
    public function scopeStudents($query)
    {
        return $query->whereHas('user', function($q) {
            $q->whereHas('roles', function($roleQuery) {
                $roleQuery->where('name', 'student');
            });
        });
    }

    /**
     * Scope: Parent profiles
     */
    public function scopeParents($query)
    {
        return $query->whereHas('user', function($q) {
            $q->whereHas('roles', function($roleQuery) {
                $roleQuery->where('name', 'parent');
            });
        });
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearchByName($query, string $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('middle_name', 'like', "%{$search}%");
        });
    }

    /**
     * Get profile data for specific role type
     */
    public function getProfileDataForRole(): array
    {
        $baseData = [
            'full_name' => $this->full_name,
            'display_name' => $this->display_name,
            'email' => $this->user->email,
            'phone' => $this->phone,
            'profile_photo_url' => $this->profile_photo_url,
            'age' => $this->age,
            'gender' => $this->gender,
            'address' => $this->formatted_address,
        ];

        if ($this->isTeacher()) {
            $baseData['teacher_data'] = [
                'employee_id' => $this->employee_id,
                'subjects_taught' => $this->subjects_taught,
                'classes_assigned' => $this->classes_assigned,
                'qualifications' => $this->qualifications,
                'experience_years' => $this->experience_years,
                'joining_date' => $this->joining_date?->format('Y-m-d'),
                'designation' => $this->designation,
                'department' => $this->department,
            ];
        }

        if ($this->isStudent()) {
            $baseData['student_data'] = [
                'student_id' => $this->student_id,
                'blood_group' => $this->blood_group,
                'medical_conditions' => $this->medical_conditions,
                'allergies' => $this->allergies,
                'transport_required' => $this->transport_required,
                'bus_route' => $this->bus_route,
                'hostel_required' => $this->hostel_required,
                'room_number' => $this->room_number,
            ];
        }

        if ($this->isParent()) {
            $baseData['parent_data'] = [
                'occupation' => $this->parent_occupation,
                'annual_income' => $this->annual_income,
                'emergency_contact' => [
                    'name' => $this->emergency_contact_name,
                    'phone' => $this->emergency_contact_phone,
                    'relationship' => $this->emergency_contact_relationship,
                ],
            ];
        }

        return $baseData;
    }
}