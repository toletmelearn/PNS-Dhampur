<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentApproval extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_version_id',
        'approver_id',
        'approval_level',
        'status',
        'comments',
        'feedback',
        'priority',
        'submitted_at',
        'reviewed_at',
        'deadline',
        'is_required',
        'delegated_to',
        'delegated_at',
        'metadata'
    ];

    protected $casts = [
        'feedback' => 'array',
        'metadata' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'deadline' => 'datetime',
        'delegated_at' => 'datetime',
        'is_required' => 'boolean',
    ];

    protected $dates = [
        'submitted_at',
        'reviewed_at',
        'deadline',
        'delegated_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_DELEGATED = 'delegated';

    /**
     * Relationships
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'document_version_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}