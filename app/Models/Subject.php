<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'class_id',
        'teacher_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function srRegisters()
    {
        return $this->hasMany(SRRegister::class);
    }

    public function examPapers()
    {
        return $this->hasMany(ExamPaper::class);
    }

    public function classTeacherPermissions()
    {
        return $this->hasMany(ClassTeacherPermission::class);
    }

    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }
}