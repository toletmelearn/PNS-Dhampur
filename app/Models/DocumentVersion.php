<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_document_id',
        'version_number',
        'original_name',
        'file_path',
        'file_extension',
        'file_size',
        'mime_type',
        'status',
        'change_summary',
        'is_current_version',
        'created_by',
        'approved_at',
        'approved_by',
        'approval_notes',
        'checksum',
        'metadata'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'is_current_version' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'approved_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ARCHIVED = 'archived';

    /**
     * Relationships
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(TeacherDocument::class, 'teacher_document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class, 'document_version_id');
    }
}