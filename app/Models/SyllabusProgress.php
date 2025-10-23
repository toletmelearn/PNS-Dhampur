<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SyllabusProgress extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'syllabus_progress';

    protected $fillable = [
        'daily_syllabus_id',
        'class_id',
        'subject_id',
        'date',
        'planned_topics',
        'completed_topics',
        'completion_percentage',
        'status',
        'remarks',
        'marked_by',
    ];

    protected $casts = [
        'date' => 'date',
        'planned_topics' => 'array',
        'completed_topics' => 'array',
        'completion_percentage' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function dailySyllabus()
    {
        return $this->belongsTo(DailySyllabus::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
