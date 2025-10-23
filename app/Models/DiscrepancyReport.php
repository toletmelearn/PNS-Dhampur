<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscrepancyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'verification_id',
        'student_id',
        'mismatches_count',
        'overall_confidence',
        'recommendation',
        'auto_resolvable',
        'status',
        'analysis',
        'created_by',
    ];

    protected $casts = [
        'analysis' => 'array',
        'auto_resolvable' => 'boolean',
        'overall_confidence' => 'decimal:2',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(StudentVerification::class, 'verification_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}