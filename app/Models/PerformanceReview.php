<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'period_start',
        'period_end',
        'reviewer_id',
        'ratings',
        'overall_score',
        'comments',
        'recommendations',
        'promotion_recommended',
        'promotion_title',
        'increment_recommended',
        'increment_amount',
        'teacher_document_id',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'ratings' => 'array',
        'recommendations' => 'array',
        'promotion_recommended' => 'boolean',
        'increment_recommended' => 'boolean',
        'overall_score' => 'decimal:2',
        'increment_amount' => 'decimal:2',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function document()
    {
        return $this->belongsTo(TeacherDocument::class, 'teacher_document_id');
    }
}