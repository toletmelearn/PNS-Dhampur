<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParentStudentRelationship extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'student_id',
        'relationship_type',
        'is_primary_guardian',
        'is_emergency_contact',
        'can_pickup_student',
        'can_authorize_medical',
        'can_view_academic_records',
        'can_receive_notifications',
        'priority_order',
        'notes',
        'created_by',
        'is_active'
    ];

    protected $casts = [
        'is_primary_guardian' => 'boolean',
        'is_emergency_contact' => 'boolean',
        'can_pickup_student' => 'boolean',
        'can_authorize_medical' => 'boolean',
        'can_view_academic_records' => 'boolean',
        'can_receive_notifications' => 'boolean',
        'priority_order' => 'integer',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'deleted_at'
    ];

    // Relationship types
    const RELATIONSHIP_FATHER = 'father';
    const RELATIONSHIP_MOTHER = 'mother';
    const RELATIONSHIP_GUARDIAN = 'guardian';
    const RELATIONSHIP_GRANDFATHER = 'grandfather';
    const RELATIONSHIP_GRANDMOTHER = 'grandmother';
    const RELATIONSHIP_UNCLE = 'uncle';
    const RELATIONSHIP_AUNT = 'aunt';
    const RELATIONSHIP_BROTHER = 'brother';
    const RELATIONSHIP_SISTER = 'sister';
    const RELATIONSHIP_OTHER = 'other';

    /**
     * Get the parent user
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /**
     * Get the student user
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the user who created this relationship
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get relationship display name
     */
    public function getDisplayNameAttribute(): string
    {
        $parentName = $this->parent->name ?? 'Unknown Parent';
        $studentName = $this->student->name ?? 'Unknown Student';
        $relationship = ucfirst($this->relationship_type);
        
        return "{$parentName} ({$relationship} of {$studentName})";
    }

    /**
     * Get relationship type display name
     */
    public function getRelationshipDisplayAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->relationship_type));
    }

    /**
     * Check if this is the primary guardian
     */
    public function isPrimaryGuardian(): bool
    {
        return $this->is_primary_guardian;
    }

    /**
     * Check if this parent can be contacted in emergencies
     */
    public function isEmergencyContact(): bool
    {
        return $this->is_emergency_contact;
    }

    /**
     * Check if this parent can pick up the student
     */
    public function canPickupStudent(): bool
    {
        return $this->can_pickup_student;
    }

    /**
     * Check if this parent can authorize medical decisions
     */
    public function canAuthorizeMedical(): bool
    {
        return $this->can_authorize_medical;
    }

    /**
     * Check if this parent can view academic records
     */
    public function canViewAcademicRecords(): bool
    {
        return $this->can_view_academic_records;
    }

    /**
     * Check if this parent should receive notifications
     */
    public function canReceiveNotifications(): bool
    {
        return $this->can_receive_notifications;
    }

    /**
     * Get permissions summary
     */
    public function getPermissionsSummary(): array
    {
        return [
            'primary_guardian' => $this->is_primary_guardian,
            'emergency_contact' => $this->is_emergency_contact,
            'pickup_student' => $this->can_pickup_student,
            'authorize_medical' => $this->can_authorize_medical,
            'view_academic_records' => $this->can_view_academic_records,
            'receive_notifications' => $this->can_receive_notifications,
        ];
    }

    /**
     * Get permissions as readable list
     */
    public function getPermissionsListAttribute(): array
    {
        $permissions = [];
        
        if ($this->is_primary_guardian) {
            $permissions[] = 'Primary Guardian';
        }
        
        if ($this->is_emergency_contact) {
            $permissions[] = 'Emergency Contact';
        }
        
        if ($this->can_pickup_student) {
            $permissions[] = 'Can Pickup Student';
        }
        
        if ($this->can_authorize_medical) {
            $permissions[] = 'Can Authorize Medical';
        }
        
        if ($this->can_view_academic_records) {
            $permissions[] = 'Can View Academic Records';
        }
        
        if ($this->can_receive_notifications) {
            $permissions[] = 'Receives Notifications';
        }
        
        return $permissions;
    }

    /**
     * Check if relationship is currently active
     */
    public function isCurrentlyActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope: Active relationships only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Relationships by parent
     */
    public function scopeByParent($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope: Relationships by student
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope: Primary guardians only
     */
    public function scopePrimaryGuardians($query)
    {
        return $query->where('is_primary_guardian', true);
    }

    /**
     * Scope: Emergency contacts only
     */
    public function scopeEmergencyContacts($query)
    {
        return $query->where('is_emergency_contact', true);
    }

    /**
     * Scope: By relationship type
     */
    public function scopeByRelationshipType($query, string $type)
    {
        return $query->where('relationship_type', $type);
    }

    /**
     * Scope: Parents who can pickup students
     */
    public function scopeCanPickup($query)
    {
        return $query->where('can_pickup_student', true);
    }

    /**
     * Scope: Parents who can authorize medical decisions
     */
    public function scopeCanAuthorizeMedical($query)
    {
        return $query->where('can_authorize_medical', true);
    }

    /**
     * Scope: Parents who can view academic records
     */
    public function scopeCanViewAcademics($query)
    {
        return $query->where('can_view_academic_records', true);
    }

    /**
     * Scope: Parents who receive notifications
     */
    public function scopeReceivesNotifications($query)
    {
        return $query->where('can_receive_notifications', true);
    }

    /**
     * Scope: Ordered by priority
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority_order', 'asc')
                    ->orderBy('is_primary_guardian', 'desc')
                    ->orderBy('is_emergency_contact', 'desc');
    }

    /**
     * Get student's parents
     */
    public static function getStudentParents($studentId, bool $activeOnly = true): array
    {
        $query = self::where('student_id', $studentId);
        
        if ($activeOnly) {
            $query->active();
        }
        
        return $query->with(['parent', 'parent.profile'])
                    ->orderedByPriority()
                    ->get()
                    ->map(function($relationship) {
                        return [
                            'relationship_id' => $relationship->id,
                            'parent_id' => $relationship->parent_id,
                            'parent_name' => $relationship->parent->name,
                            'parent_email' => $relationship->parent->email,
                            'parent_phone' => $relationship->parent->phone,
                            'relationship_type' => $relationship->relationship_type,
                            'relationship_display' => $relationship->relationship_display,
                            'is_primary_guardian' => $relationship->is_primary_guardian,
                            'is_emergency_contact' => $relationship->is_emergency_contact,
                            'permissions' => $relationship->getPermissionsSummary(),
                            'permissions_list' => $relationship->permissions_list,
                            'priority_order' => $relationship->priority_order,
                        ];
                    })
                    ->toArray();
    }

    /**
     * Get parent's children
     */
    public static function getParentChildren($parentId, bool $activeOnly = true): array
    {
        $query = self::where('parent_id', $parentId);
        
        if ($activeOnly) {
            $query->active();
        }
        
        return $query->with(['student', 'student.profile'])
                    ->orderBy('priority_order')
                    ->get()
                    ->map(function($relationship) {
                        return [
                            'relationship_id' => $relationship->id,
                            'student_id' => $relationship->student_id,
                            'student_name' => $relationship->student->name,
                            'relationship_type' => $relationship->relationship_type,
                            'relationship_display' => $relationship->relationship_display,
                            'is_primary_guardian' => $relationship->is_primary_guardian,
                            'permissions' => $relationship->getPermissionsSummary(),
                            'priority_order' => $relationship->priority_order,
                        ];
                    })
                    ->toArray();
    }

    /**
     * Get primary guardian for student
     */
    public static function getPrimaryGuardian($studentId)
    {
        return self::where('student_id', $studentId)
                  ->where('is_primary_guardian', true)
                  ->active()
                  ->with(['parent', 'parent.profile'])
                  ->first();
    }

    /**
     * Get emergency contacts for student
     */
    public static function getEmergencyContacts($studentId): array
    {
        return self::where('student_id', $studentId)
                  ->where('is_emergency_contact', true)
                  ->active()
                  ->with(['parent', 'parent.profile'])
                  ->orderedByPriority()
                  ->get()
                  ->map(function($relationship) {
                      return [
                          'parent_id' => $relationship->parent_id,
                          'parent_name' => $relationship->parent->name,
                          'parent_email' => $relationship->parent->email,
                          'parent_phone' => $relationship->parent->phone,
                          'relationship_type' => $relationship->relationship_display,
                          'priority_order' => $relationship->priority_order,
                          'can_pickup' => $relationship->can_pickup_student,
                          'can_authorize_medical' => $relationship->can_authorize_medical,
                      ];
                  })
                  ->toArray();
    }

    /**
     * Get parents who can pickup student
     */
    public static function getPickupAuthorizedParents($studentId): array
    {
        return self::where('student_id', $studentId)
                  ->where('can_pickup_student', true)
                  ->active()
                  ->with(['parent', 'parent.profile'])
                  ->orderedByPriority()
                  ->get()
                  ->map(function($relationship) {
                      return [
                          'parent_id' => $relationship->parent_id,
                          'parent_name' => $relationship->parent->name,
                          'parent_phone' => $relationship->parent->phone,
                          'relationship_type' => $relationship->relationship_display,
                          'is_primary_guardian' => $relationship->is_primary_guardian,
                          'is_emergency_contact' => $relationship->is_emergency_contact,
                      ];
                  })
                  ->toArray();
    }

    /**
     * Get parents who should receive notifications
     */
    public static function getNotificationRecipients($studentId): array
    {
        return self::where('student_id', $studentId)
                  ->where('can_receive_notifications', true)
                  ->active()
                  ->with(['parent', 'parent.profile'])
                  ->get()
                  ->map(function($relationship) {
                      return [
                          'parent_id' => $relationship->parent_id,
                          'parent_name' => $relationship->parent->name,
                          'parent_email' => $relationship->parent->email,
                          'parent_phone' => $relationship->parent->phone,
                          'relationship_type' => $relationship->relationship_display,
                          'priority_order' => $relationship->priority_order,
                      ];
                  })
                  ->toArray();
    }

    /**
     * Check if parent has access to student
     */
    public static function hasParentAccess($parentId, $studentId, string $permission = null): bool
    {
        $query = self::where('parent_id', $parentId)
                    ->where('student_id', $studentId)
                    ->active();
        
        if ($permission) {
            switch ($permission) {
                case 'pickup':
                    $query->where('can_pickup_student', true);
                    break;
                case 'medical':
                    $query->where('can_authorize_medical', true);
                    break;
                case 'academic':
                    $query->where('can_view_academic_records', true);
                    break;
                case 'notifications':
                    $query->where('can_receive_notifications', true);
                    break;
            }
        }
        
        return $query->exists();
    }

    /**
     * Create parent-student relationship
     */
    public static function createRelationship(array $data): self
    {
        // Set default values
        $data['is_active'] = $data['is_active'] ?? true;
        $data['can_view_academic_records'] = $data['can_view_academic_records'] ?? true;
        $data['can_receive_notifications'] = $data['can_receive_notifications'] ?? true;
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        
        // Set priority order if not provided
        if (empty($data['priority_order'])) {
            $maxPriority = self::where('student_id', $data['student_id'])->max('priority_order') ?? 0;
            $data['priority_order'] = $maxPriority + 1;
        }
        
        // If this is set as primary guardian, remove primary status from others
        if ($data['is_primary_guardian'] ?? false) {
            self::where('student_id', $data['student_id'])
                ->where('is_primary_guardian', true)
                ->update(['is_primary_guardian' => false]);
        }
        
        return self::create($data);
    }

    /**
     * Set as primary guardian
     */
    public function setAsPrimaryGuardian(): void
    {
        // Remove primary status from other relationships for this student
        self::where('student_id', $this->student_id)
            ->where('id', '!=', $this->id)
            ->where('is_primary_guardian', true)
            ->update(['is_primary_guardian' => false]);
        
        // Set this relationship as primary
        $this->is_primary_guardian = true;
        $this->priority_order = 1;
        $this->save();
        
        // Update priority order for other relationships
        $this->reorderPriorities();
    }

    /**
     * Reorder priorities for student's relationships
     */
    protected function reorderPriorities(): void
    {
        $relationships = self::where('student_id', $this->student_id)
                            ->active()
                            ->orderBy('is_primary_guardian', 'desc')
                            ->orderBy('is_emergency_contact', 'desc')
                            ->orderBy('priority_order')
                            ->get();
        
        foreach ($relationships as $index => $relationship) {
            $relationship->priority_order = $index + 1;
            $relationship->save();
        }
    }

    /**
     * Deactivate relationship
     */
    public function deactivate(string $reason = null): void
    {
        $this->is_active = false;
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Deactivated: {$reason}";
        }
        
        $this->save();
        
        // If this was the primary guardian, promote another relationship
        if ($this->is_primary_guardian) {
            $nextPrimary = self::where('student_id', $this->student_id)
                              ->where('id', '!=', $this->id)
                              ->active()
                              ->orderBy('priority_order')
                              ->first();
            
            if ($nextPrimary) {
                $nextPrimary->setAsPrimaryGuardian();
            }
        }
    }

    /**
     * Get available relationship types
     */
    public static function getRelationshipTypes(): array
    {
        return [
            self::RELATIONSHIP_FATHER => 'Father',
            self::RELATIONSHIP_MOTHER => 'Mother',
            self::RELATIONSHIP_GUARDIAN => 'Guardian',
            self::RELATIONSHIP_GRANDFATHER => 'Grandfather',
            self::RELATIONSHIP_GRANDMOTHER => 'Grandmother',
            self::RELATIONSHIP_UNCLE => 'Uncle',
            self::RELATIONSHIP_AUNT => 'Aunt',
            self::RELATIONSHIP_BROTHER => 'Brother',
            self::RELATIONSHIP_SISTER => 'Sister',
            self::RELATIONSHIP_OTHER => 'Other',
        ];
    }

    /**
     * Get relationship statistics for a student
     */
    public static function getStudentRelationshipStats($studentId): array
    {
        $relationships = self::where('student_id', $studentId)->get();
        
        return [
            'total_relationships' => $relationships->count(),
            'active_relationships' => $relationships->where('is_active', true)->count(),
            'primary_guardians' => $relationships->where('is_primary_guardian', true)->count(),
            'emergency_contacts' => $relationships->where('is_emergency_contact', true)->count(),
            'pickup_authorized' => $relationships->where('can_pickup_student', true)->count(),
            'medical_authorized' => $relationships->where('can_authorize_medical', true)->count(),
            'academic_access' => $relationships->where('can_view_academic_records', true)->count(),
            'notification_recipients' => $relationships->where('can_receive_notifications', true)->count(),
            'relationship_types' => $relationships->groupBy('relationship_type')->map->count()->toArray(),
        ];
    }
}