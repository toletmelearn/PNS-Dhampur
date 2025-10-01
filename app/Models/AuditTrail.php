<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
        'student_id',
        'class_id',
        'subject_id',
        'academic_year',
        'term',
        'correction_reason',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'status'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    protected $dates = [
        'approved_at',
        'rejected_at',
        'created_at',
        'updated_at'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModel($query, $model)
    {
        return $query->where('auditable_type', get_class($model))
                    ->where('auditable_id', $model->id);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
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

    public function scopeForTerm($query, $term)
    {
        return $query->where('term', $term);
    }

    public function scopeByEvent($query, $event)
    {
        return $query->where('event', $event);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getChangedFieldsAttribute()
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changed = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue != $newValue) {
                $changed[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changed;
    }

    public function getFormattedChangesAttribute()
    {
        $changes = $this->changed_fields;
        $formatted = [];

        foreach ($changes as $field => $change) {
            $fieldName = ucwords(str_replace('_', ' ', $field));
            $oldValue = $change['old'] ?? 'N/A';
            $newValue = $change['new'] ?? 'N/A';
            
            $formatted[] = "{$fieldName}: {$oldValue} → {$newValue}";
        }

        return implode(', ', $formatted);
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending_approval' => '<span class="badge bg-warning">Pending Approval</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            'auto_approved' => '<span class="badge bg-info">Auto Approved</span>',
            default => '<span class="badge bg-secondary">Unknown</span>'
        };
    }

    public function getEventIconAttribute()
    {
        return match($this->event) {
            'created' => 'fas fa-plus-circle text-success',
            'updated' => 'fas fa-edit text-primary',
            'deleted' => 'fas fa-trash text-danger',
            'restored' => 'fas fa-undo text-info',
            'correction' => 'fas fa-tools text-warning',
            default => 'fas fa-circle text-muted'
        };
    }

    // Methods
    public function approve($approvedBy = null, $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy ?? auth()->id(),
            'approved_at' => now(),
            'correction_reason' => $this->correction_reason . ($notes ? "\nApproval Notes: {$notes}" : '')
        ]);

        return $this;
    }

    public function reject($rejectedBy = null, $reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => $rejectedBy ?? auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason ?? 'No reason provided'
        ]);

        return $this;
    }

    public static function logActivity($model, $event, $oldValues = [], $newValues = [], $options = [])
    {
        $user = auth()->user();
        
        return self::create([
            'user_id' => $user ? $user->id : null,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => request()->fullUrl(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'tags' => $options['tags'] ?? [],
            'student_id' => $options['student_id'] ?? ($model instanceof Student ? $model->id : null),
            'class_id' => $options['class_id'] ?? null,
            'subject_id' => $options['subject_id'] ?? null,
            'academic_year' => $options['academic_year'] ?? date('Y'),
            'term' => $options['term'] ?? null,
            'correction_reason' => $options['correction_reason'] ?? null,
            'status' => $options['status'] ?? ($event === 'correction' ? 'pending_approval' : 'auto_approved')
        ]);
    }

    public static function logCorrection($model, $oldValues, $newValues, $reason, $options = [])
    {
        return self::logActivity($model, 'correction', $oldValues, $newValues, array_merge($options, [
            'correction_reason' => $reason,
            'status' => 'pending_approval'
        ]));
    }

    public static function getActivitySummary($userId = null, $days = 30)
    {
        $query = self::recent($days);
        
        if ($userId) {
            $query->forUser($userId);
        }

        $activities = $query->get();

        return [
            'total_activities' => $activities->count(),
            'by_event' => $activities->groupBy('event')->map->count(),
            'by_status' => $activities->groupBy('status')->map->count(),
            'pending_approvals' => $activities->where('status', 'pending_approval')->count(),
            'recent_corrections' => $activities->where('event', 'correction')->take(10)
        ];
    }

    public static function getStudentActivityHistory($studentId, $limit = 50)
    {
        return self::forStudent($studentId)
                  ->with(['user', 'approvedBy', 'rejectedBy'])
                  ->orderBy('created_at', 'desc')
                  ->limit($limit)
                  ->get();
    }

    public static function getClassActivityReport($classId, $academicYear = null, $term = null)
    {
        $query = self::forClass($classId);

        if ($academicYear) {
            $query->forAcademicYear($academicYear);
        }

        if ($term) {
            $query->forTerm($term);
        }

        return $query->with(['user', 'student', 'subject'])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->groupBy(['event', 'status']);
    }
}