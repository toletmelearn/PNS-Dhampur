<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentProgress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_progress';

    protected $fillable = [
        'student_id',
        'class_model_id',
        'class_data_id',
        'progress',
        'term',
        'period_start',
        'period_end',
        'created_by',
        'updated_by',
        'approval_status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'progress' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
        'approved_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_model_id');
    }

    public function classData()
    {
        return $this->belongsTo(ClassData::class, 'class_data_id');
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
}