<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataVersion extends Model
{
    use HasFactory, SoftDeletes;

    // Map to existing class_data_versions table
    protected $table = 'class_data_versions';

    protected $fillable = [
        'class_data_id',
        'version_number',
        'data_snapshot',
        'checksum',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'data_snapshot' => 'array',
        'metadata' => 'array',
    ];

    public function classData()
    {
        return $this->belongsTo(ClassData::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function audit()
    {
        return $this->belongsTo(ClassDataAudit::class, 'audit_id');
    }
}