<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherExperience extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'total_years',
        'primary_specialization',
        'specializations',
        'summary',
        'achievements',
        'last_promotion_date',
        'portfolio_status',
    ];

    protected $casts = [
        'specializations' => 'array',
        'last_promotion_date' => 'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function employmentHistories()
    {
        return $this->hasMany(EmploymentHistory::class, 'teacher_id', 'teacher_id');
    }

    public function certifications()
    {
        return $this->hasMany(Certification::class, 'teacher_id', 'teacher_id');
    }

    public function performanceReviews()
    {
        return $this->hasMany(PerformanceReview::class, 'teacher_id', 'teacher_id');
    }
}