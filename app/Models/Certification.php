<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'name',
        'issuing_organization',
        'issue_date',
        'expiry_date',
        'certificate_code',
        'score',
        'teacher_document_id',
        'document_path',
        'verified',
        'license_number',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'score' => 'decimal:2',
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