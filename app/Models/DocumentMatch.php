<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'verification_id',
        'field',
        'expected_value',
        'document_value',
        'similarity_score',
        'confidence',
        'mismatch_type',
        'severity',
        'auto_resolvable',
        'suggestions',
        'source_document_type'
    ];

    protected $casts = [
        'suggestions' => 'array',
        'auto_resolvable' => 'boolean',
        'similarity_score' => 'decimal:2',
        'confidence' => 'decimal:2',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(StudentVerification::class, 'verification_id');
    }
}