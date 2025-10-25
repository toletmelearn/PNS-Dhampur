<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class StudentVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'verification_type',
        'status',
        'match_score',
        'document_type',
        'verification_status',
        'verification_method',
        'extracted_data',
        'verification_results',
        'confidence_score',
        'uploaded_by',
        'verified_by',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'verification_results' => 'array',
        'match_score' => 'float',
        'confidence_score' => 'float',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';
    const STATUS_MANUAL_REVIEW = 'manual_review';
}