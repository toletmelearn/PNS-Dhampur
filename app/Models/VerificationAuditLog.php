<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationAuditLog extends Model
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
    ];

    protected $casts = [
        'details' => 'array',
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the verification that this audit log belongs to.
     */
    public function verification()
    {
        return $this->belongsTo(StudentVerification::class, 'verification_id');
    }

    /**
     * Get the student that this audit log belongs to.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the user who performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Scope to filter by action type.
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by verification.
     */
    public function scopeByVerification($query, $verificationId)
    {
        return $query->where('verification_id', $verificationId);
    }

    /**
     * Scope to filter by student.
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
