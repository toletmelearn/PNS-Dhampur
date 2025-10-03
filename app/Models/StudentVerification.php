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
        'document_type',
        'original_file_path',
        'processed_file_path',
        'verification_status',
        'verification_method',
        'extracted_data',
        'verification_results',
        'confidence_score',
        'format_valid',
        'quality_check_passed',
        'data_consistency_check',
        'cross_reference_check',
        'reviewed_by',
        'reviewer_comments',
        'reviewed_at',
        'verification_log',
        'uploaded_by',
        'verification_started_at',
        'verification_completed_at',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'verification_results' => 'array',
        'verification_log' => 'array',
        'confidence_score' => 'decimal:2',
        'format_valid' => 'boolean',
        'quality_check_passed' => 'boolean',
        'data_consistency_check' => 'boolean',
        'cross_reference_check' => 'boolean',
        'reviewed_at' => 'datetime',
        'verification_started_at' => 'datetime',
        'verification_completed_at' => 'datetime',
    ];

    // Document types
    const DOCUMENT_TYPES = [
        'birth_certificate' => 'Birth Certificate',
        'aadhar_card' => 'Aadhar Card',
        'transfer_certificate' => 'Transfer Certificate',
        'caste_certificate' => 'Caste Certificate',
        'income_certificate' => 'Income Certificate',
        'domicile_certificate' => 'Domicile Certificate',
        'passport_photo' => 'Passport Photo',
        'previous_marksheet' => 'Previous Marksheet',
    ];

    // Verification statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_VERIFIED = 'verified';
    const STATUS_FAILED = 'failed';
    const STATUS_MANUAL_REVIEW = 'manual_review';

    // Verification methods
    const METHOD_AUTOMATIC = 'automatic';
    const METHOD_MANUAL = 'manual';
    const METHOD_HYBRID = 'hybrid';

    // Confidence thresholds
    const HIGH_CONFIDENCE_THRESHOLD = 85.0;
    const MEDIUM_CONFIDENCE_THRESHOLD = 60.0;
    const LOW_CONFIDENCE_THRESHOLD = 40.0;

    /**
     * Relationships
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('verification_status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('verification_status', self::STATUS_PROCESSING);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::STATUS_VERIFIED);
    }

    public function scopeFailed($query)
    {
        return $query->where('verification_status', self::STATUS_FAILED);
    }

    public function scopeManualReview($query)
    {
        return $query->where('verification_status', self::STATUS_MANUAL_REVIEW);
    }

    public function scopeHighConfidence($query)
    {
        return $query->where('confidence_score', '>=', self::HIGH_CONFIDENCE_THRESHOLD);
    }

    public function scopeMediumConfidence($query)
    {
        return $query->whereBetween('confidence_score', [self::MEDIUM_CONFIDENCE_THRESHOLD, self::HIGH_CONFIDENCE_THRESHOLD - 0.01]);
    }

    public function scopeLowConfidence($query)
    {
        return $query->where('confidence_score', '<', self::MEDIUM_CONFIDENCE_THRESHOLD);
    }

    public function scopeByDocumentType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    /**
     * Accessors
     */
    public function getDocumentTypeNameAttribute()
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? ucfirst(str_replace('_', ' ', $this->document_type));
    }

    public function getOriginalFileUrlAttribute()
    {
        return $this->original_file_path ? Storage::url($this->original_file_path) : null;
    }

    public function getProcessedFileUrlAttribute()
    {
        return $this->processed_file_path ? Storage::url($this->processed_file_path) : null;
    }

    public function getFileSizeAttribute()
    {
        if (!$this->original_file_path || !Storage::exists($this->original_file_path)) {
            return 0;
        }
        
        return Storage::size($this->original_file_path);
    }

    public function getHumanFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getConfidenceLevelAttribute()
    {
        if ($this->confidence_score >= self::HIGH_CONFIDENCE_THRESHOLD) {
            return 'High';
        } elseif ($this->confidence_score >= self::MEDIUM_CONFIDENCE_THRESHOLD) {
            return 'Medium';
        } elseif ($this->confidence_score >= self::LOW_CONFIDENCE_THRESHOLD) {
            return 'Low';
        }
        return 'Very Low';
    }

    public function getVerificationDurationAttribute()
    {
        if (!$this->verification_started_at || !$this->verification_completed_at) {
            return null;
        }
        
        return $this->verification_started_at->diffInSeconds($this->verification_completed_at);
    }

    public function getIsCompleteAttribute()
    {
        return in_array($this->verification_status, [self::STATUS_VERIFIED, self::STATUS_FAILED]);
    }

    public function getRequiresManualReviewAttribute()
    {
        return $this->verification_status === self::STATUS_MANUAL_REVIEW || 
               $this->confidence_score < self::MEDIUM_CONFIDENCE_THRESHOLD;
    }

    /**
     * Helper Methods
     */
    public function markAsProcessing()
    {
        $this->update([
            'verification_status' => self::STATUS_PROCESSING,
            'verification_started_at' => now(),
        ]);
    }

    public function markAsVerified($confidenceScore = null, $results = [])
    {
        $this->update([
            'verification_status' => self::STATUS_VERIFIED,
            'confidence_score' => $confidenceScore,
            'verification_results' => $results,
            'verification_completed_at' => now(),
        ]);
    }

    public function markAsFailed($reason = null, $results = [])
    {
        $this->update([
            'verification_status' => self::STATUS_FAILED,
            'verification_results' => array_merge($results, ['failure_reason' => $reason]),
            'verification_completed_at' => now(),
        ]);
    }

    public function markForManualReview($reason = null)
    {
        $this->update([
            'verification_status' => self::STATUS_MANUAL_REVIEW,
            'verification_results' => ['manual_review_reason' => $reason],
        ]);
    }

    public function addToVerificationLog($step, $data = [])
    {
        $log = $this->verification_log ?? [];
        $log[] = [
            'step' => $step,
            'timestamp' => now()->toISOString(),
            'data' => $data,
        ];
        
        $this->update(['verification_log' => $log]);
    }

    public function approveManually($reviewerId, $comments = null)
    {
        $this->update([
            'verification_status' => self::STATUS_VERIFIED,
            'reviewed_by' => $reviewerId,
            'reviewer_comments' => $comments,
            'reviewed_at' => now(),
            'verification_completed_at' => now(),
        ]);
    }

    public function rejectManually($reviewerId, $comments = null)
    {
        $this->update([
            'verification_status' => self::STATUS_FAILED,
            'reviewed_by' => $reviewerId,
            'reviewer_comments' => $comments,
            'reviewed_at' => now(),
            'verification_completed_at' => now(),
        ]);
    }

    public function deleteFiles()
    {
        if ($this->original_file_path && Storage::exists($this->original_file_path)) {
            Storage::delete($this->original_file_path);
        }
        
        if ($this->processed_file_path && Storage::exists($this->processed_file_path)) {
            Storage::delete($this->processed_file_path);
        }
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($verification) {
            $verification->deleteFiles();
        });
    }
}