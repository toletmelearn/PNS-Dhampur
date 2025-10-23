<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChangeLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'change_logs';

    protected $fillable = [
        'changeable_type',
        'changeable_id',
        'user_id',
        'action',
        'changed_fields',
        'old_values',
        'new_values',
        'significant',
        'approved_by',
        'approved_at',
        'audit_id',
        'ip_address',
        'user_agent',
        'session_id',
        'batch_id',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'significant' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function changeable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function audit()
    {
        return $this->belongsTo(ClassDataAudit::class, 'audit_id');
    }
}