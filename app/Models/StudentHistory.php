<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_histories';

    protected $fillable = [
        'student_id',
        'academic_year',
        'class_id',
        'section',
        'status',
        'history_data',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'history_data' => 'array',
    ];

    protected $dates = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
