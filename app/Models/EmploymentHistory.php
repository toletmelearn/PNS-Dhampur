<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmploymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'organization_name',
        'role_title',
        'start_date',
        'end_date',
        'responsibilities',
        'subjects_taught',
        'achievements',
        'teacher_document_id',
        'document_path',
        'city',
        'country',
        'verified',
        'verification_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'subjects_taught' => 'array',
        'verified' => 'boolean',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function document()
    {
        return $this->belongsTo(TeacherDocument::class, 'teacher_document_id');
    }
}