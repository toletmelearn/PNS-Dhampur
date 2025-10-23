<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentAccessLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_access_logs';

    protected $fillable = [
        'user_id',
        'material_id',
        'accessed_at',
        'ip_address',
        'user_agent',
        'device_info',
        'success',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
        'device_info' => 'array',
        'success' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function material()
    {
        return $this->belongsTo(SubjectMaterial::class, 'material_id');
    }
}
