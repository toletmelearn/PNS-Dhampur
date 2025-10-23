<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailySyllabus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'daily_syllabi';

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'date',
        'title',
        'description',
        'tags',
        'visibility',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'tags' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function materials()
    {
        return $this->hasMany(SubjectMaterial::class);
    }

    public function progresses()
    {
        return $this->hasMany(SyllabusProgress::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helpers
    public function latestProgress()
    {
        return $this->hasOne(SyllabusProgress::class)->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
