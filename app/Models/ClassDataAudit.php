<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class ClassDataAudit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'class_data_audits';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'status',
        'event_type',
        'old_values',
        'new_values',
        'changed_fields',
        'user_id',
        'user_type',
        'ip_address',
        'user_agent',
        'session_id',
        'request_id',
        'description',
        'metadata',
        'risk_level',
        'requires_approval',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'batch_id',
        'parent_audit_id',
        'checksum',
        'tags'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $dates = [
        'approved_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Event types
    const EVENT_CREATED = 'created';
    const EVENT_UPDATED = 'updated';
    const EVENT_DELETED = 'deleted';
    const EVENT_RESTORED = 'restored';
    const EVENT_BULK_UPDATE = 'bulk_update';
    const EVENT_BULK_DELETE = 'bulk_delete';
    const EVENT_IMPORT = 'import';
    const EVENT_EXPORT = 'export';
    const EVENT_PERMISSION_CHANGE = 'permission_change';
    const EVENT_STATUS_CHANGE = 'status_change';

    // Risk levels
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    // Approval statuses
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REJECTED = 'rejected';
    const APPROVAL_AUTO_APPROVED = 'auto_approved';

    /**
     * Relationships
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function parentAudit()
    {
        return $this->belongsTo(ClassDataAudit::class, 'parent_audit_id');
    }

    public function childAudits()
    {
        return $this->hasMany(ClassDataAudit::class, 'parent_audit_id');
    }

    public function versions()
    {
        return $this->hasMany(ClassDataVersion::class, 'audit_id');
    }

    public function approvals()
    {
        return $this->hasMany(ClassDataApproval::class, 'audit_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'entity_id')->where('entity_type', 'class');
    }

    /**
     * Scopes
     */
    public function scopeForModel($query, $model)
    {
        return $query->where('auditable_type', get_class($model))
                    ->where('auditable_id', $model->id);
    }

    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeByRiskLevel($query, $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    public function scopeRequiringApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', self::APPROVAL_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', self::APPROVAL_REJECTED);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInBatch($query, $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    public function scopeWithTags($query, array $tags)
    {
        return $query->whereJsonContains('tags', $tags);
    }

    /**
     * Accessors for backward compatibility
     */
    public function getEntityTypeAttribute()
    {
        return $this->auditable_type;
    }

    public function getEntityIdAttribute()
    {
        return $this->auditable_id;
    }

    /**
     * Accessors & Mutators
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('M d, Y H:i:s');
    }

    public function getEventTypeDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->event_type));
    }

    public function getRiskLevelDisplayAttribute()
    {
        return ucfirst($this->risk_level);
    }

    public function getApprovalStatusDisplayAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->approval_status));
    }

    public function getChangedFieldsCountAttribute()
    {
        return is_array($this->changed_fields) ? count($this->changed_fields) : 0;
    }

    /**
     * Helper Methods
     */
    public function isHighRisk()
    {
        return in_array($this->risk_level, [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    public function isPendingApproval()
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    public function isApproved()
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isRejected()
    {
        return $this->approval_status === self::APPROVAL_REJECTED;
    }

    public function canBeApproved()
    {
        return $this->requires_approval && $this->isPendingApproval();
    }

    public function getFieldChanges($field = null)
    {
        if ($field) {
            return [
                'old' => $this->old_values[$field] ?? null,
                'new' => $this->new_values[$field] ?? null
            ];
        }

        $changes = [];
        foreach ($this->changed_fields as $field) {
            $changes[$field] = [
                'old' => $this->old_values[$field] ?? null,
                'new' => $this->new_values[$field] ?? null
            ];
        }

        return $changes;
    }

    public function generateChecksum()
    {
        $data = [
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'event_type' => $this->event_type,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at->toISOString()
        ];

        return hash('sha256', json_encode($data, 64)); // JSON_SORT_KEYS equivalent
    }

    public function verifyIntegrity()
    {
        return $this->checksum === $this->generateChecksum();
    }

    /**
     * Static Methods
     */
    public static function createAuditLog($model, $eventType, $oldValues = [], $newValues = [], $options = [])
    {
        $changedFields = array_keys(array_merge($oldValues, $newValues));
        
        $audit = new static([
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'event_type' => $eventType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'user_id' => auth()->id(),
            'user_type' => auth()->user() ? get_class(auth()->user()) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'request_id' => request()->header('X-Request-ID', uniqid()),
            'description' => $options['description'] ?? null,
            'metadata' => $options['metadata'] ?? [],
            'risk_level' => $options['risk_level'] ?? self::RISK_LOW,
            'requires_approval' => $options['requires_approval'] ?? false,
            'approval_status' => $options['requires_approval'] ?? false ? self::APPROVAL_PENDING : self::APPROVAL_AUTO_APPROVED,
            'batch_id' => $options['batch_id'] ?? null,
            'parent_audit_id' => $options['parent_audit_id'] ?? null,
            'tags' => $options['tags'] ?? []
        ]);

        $audit->checksum = $audit->generateChecksum();
        $audit->save();

        return $audit;
    }

    public static function getAuditStatistics($days = 30)
    {
        $startDate = Carbon::now()->subDays($days);

        return [
            'total_audits' => static::where('created_at', '>=', $startDate)->count(),
            'by_event_type' => static::where('created_at', '>=', $startDate)
                ->selectRaw('event_type, COUNT(*) as count')
                ->groupBy('event_type')
                ->pluck('count', 'event_type')
                ->toArray(),
            'by_risk_level' => static::where('created_at', '>=', $startDate)
                ->selectRaw('risk_level, COUNT(*) as count')
                ->groupBy('risk_level')
                ->pluck('count', 'risk_level')
                ->toArray(),
            'pending_approvals' => static::pendingApproval()->count(),
            'high_risk_events' => static::where('created_at', '>=', $startDate)
                ->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL])
                ->count(),
            'unique_users' => static::where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->count('user_id')
        ];
    }
}