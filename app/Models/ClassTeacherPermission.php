<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassTeacherPermission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject_id',
        'permission_type',
        'can_view_records',
        'can_edit_records',
        'can_delete_records',
        'can_approve_corrections',
        'can_view_audit_trail',
        'can_export_reports',
        'can_bulk_operations',
        'academic_year',
        'valid_from',
        'valid_until',
        'granted_by',
        'revoked_by',
        'revoked_at',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'can_view_records' => 'boolean',
        'can_edit_records' => 'boolean',
        'can_delete_records' => 'boolean',
        'can_approve_corrections' => 'boolean',
        'can_view_audit_trail' => 'boolean',
        'can_export_reports' => 'boolean',
        'can_bulk_operations' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'revoked_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'valid_from',
        'valid_until',
        'revoked_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->whereNull('revoked_at')
                    ->where(function ($q) {
                        $q->whereNull('valid_until')
                          ->orWhere('valid_until', '>=', now());
                    });
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByPermissionType($query, $type)
    {
        return $query->where('permission_type', $type);
    }

    // Accessors
    public function getIsValidAttribute()
    {
        return $this->is_active && 
               !$this->revoked_at && 
               (!$this->valid_until || $this->valid_until >= now());
    }

    public function getPermissionSummaryAttribute()
    {
        $permissions = [];
        
        if ($this->can_view_records) $permissions[] = 'View';
        if ($this->can_edit_records) $permissions[] = 'Edit';
        if ($this->can_delete_records) $permissions[] = 'Delete';
        if ($this->can_approve_corrections) $permissions[] = 'Approve';
        if ($this->can_view_audit_trail) $permissions[] = 'Audit';
        if ($this->can_export_reports) $permissions[] = 'Export';
        if ($this->can_bulk_operations) $permissions[] = 'Bulk';

        return implode(', ', $permissions);
    }

    public function getValidityStatusAttribute()
    {
        if (!$this->is_active) return 'Inactive';
        if ($this->revoked_at) return 'Revoked';
        if ($this->valid_until && $this->valid_until < now()) return 'Expired';
        if ($this->valid_from && $this->valid_from > now()) return 'Pending';
        
        return 'Active';
    }

    // Methods
    public function hasPermission($permission)
    {
        if (!$this->is_valid) return false;

        return match($permission) {
            'view' => $this->can_view_records,
            'edit' => $this->can_edit_records,
            'delete' => $this->can_delete_records,
            'approve' => $this->can_approve_corrections,
            'audit' => $this->can_view_audit_trail,
            'export' => $this->can_export_reports,
            'bulk' => $this->can_bulk_operations,
            default => false
        };
    }

    public function revoke($revokedBy = null, $reason = null)
    {
        $this->update([
            'is_active' => false,
            'revoked_at' => now(),
            'revoked_by' => $revokedBy,
            'notes' => $this->notes . "\nRevoked: " . ($reason ?? 'No reason provided')
        ]);

        return $this;
    }

    public function extend($newValidUntil, $extendedBy = null)
    {
        $this->update([
            'valid_until' => $newValidUntil,
            'notes' => $this->notes . "\nExtended until: {$newValidUntil}"
        ]);

        return $this;
    }

    public static function grantPermission($teacherId, $classId, $subjectId = null, $permissions = [], $options = [])
    {
        $defaultPermissions = [
            'can_view_records' => true,
            'can_edit_records' => false,
            'can_delete_records' => false,
            'can_approve_corrections' => false,
            'can_view_audit_trail' => false,
            'can_export_reports' => false,
            'can_bulk_operations' => false
        ];

        $permissionData = array_merge($defaultPermissions, $permissions);

        return self::create(array_merge([
            'teacher_id' => $teacherId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'permission_type' => $options['type'] ?? 'class_teacher',
            'academic_year' => $options['academic_year'] ?? date('Y'),
            'valid_from' => $options['valid_from'] ?? now(),
            'valid_until' => $options['valid_until'] ?? null,
            'granted_by' => $options['granted_by'] ?? auth()->id(),
            'is_active' => true
        ], $permissionData, $options));
    }

    public static function getTeacherPermissions($teacherId, $classId = null, $subjectId = null)
    {
        $query = self::active()->forTeacher($teacherId);

        if ($classId) {
            $query->forClass($classId);
        }

        if ($subjectId) {
            $query->forSubject($subjectId);
        }

        return $query->get();
    }

    public static function checkPermission($teacherId, $permission, $classId = null, $subjectId = null)
    {
        $permissions = self::getTeacherPermissions($teacherId, $classId, $subjectId);

        return $permissions->contains(function ($perm) use ($permission) {
            return $perm->hasPermission($permission);
        });
    }
}