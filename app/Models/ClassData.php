<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassData extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'class_data';

    protected $fillable = [
        'class_model_id',
        'class_name',
        'subject',
        'data',
        'metadata',
        'status',
        'original_data',
        'approval_status',
        'approval_required',
        'approved_by',
        'approved_at',
        'last_version_id',
        'significant_change',
        'change_reason',
        'created_by',
        'updated_by',
        'is_active',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'original_data' => 'array',
        'approval_required' => 'boolean',
        'significant_change' => 'boolean',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_model_id');
    }

    public function lastVersion()
    {
        return $this->belongsTo(ClassDataVersion::class, 'last_version_id');
    }

    public function versions()
    {
        return $this->hasMany(ClassDataVersion::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function changeLogs()
    {
        return $this->morphMany(ChangeLog::class, 'changeable');
    }

    public function audits()
    {
        return $this->morphMany(ClassDataAudit::class, 'auditable');
    }

    public function studentProgress()
    {
        return $this->hasMany(StudentProgress::class);
    }
}