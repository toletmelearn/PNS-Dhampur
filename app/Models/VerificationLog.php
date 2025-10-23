<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationLog extends Model
{
    use HasFactory;

    protected $table = 'verification_audit_logs';

    protected $fillable = [
        'verification_id',
        'student_id',
        'user_id',
        'action',
        'details',
        'old_data',
        'new_data',
        'ip_address',
        'user_agent',
        'url',
    ];

    protected $casts = [
        'details' => 'array',
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(StudentVerification::class, 'verification_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}