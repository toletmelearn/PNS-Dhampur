<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DocumentVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'document_type',
        'document_name',
        'file_path',
        'file_hash',
        'verification_status',
        'verification_notes',
        'verified_by',
        'verified_at',
        'expiry_date',
        'metadata',
        'is_mandatory',
        'verification_attempts',
        'last_verification_attempt'
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expiry_date' => 'date',
        'last_verification_attempt' => 'datetime',
        'metadata' => 'array',
        'is_mandatory' => 'boolean'
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopeExpired($query)
    {
        return $query->where('verification_status', 'expired')
                    ->orWhere(function($q) {
                        $q->whereNotNull('expiry_date')
                          ->where('expiry_date', '<', now());
                    });
    }

    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    // Methods
    public function isExpired(): bool
    {
        if ($this->expiry_date) {
            return Carbon::parse($this->expiry_date)->isPast();
        }
        return false;
    }

    public function verify(User $user, string $notes = null): bool
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_by' => $user->id,
            'verified_at' => now(),
            'verification_notes' => $notes
        ]);

        return true;
    }

    public function reject(User $user, string $notes): bool
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_by' => $user->id,
            'verified_at' => now(),
            'verification_notes' => $notes
        ]);

        return true;
    }

    public function incrementVerificationAttempts(): void
    {
        $this->increment('verification_attempts');
        $this->update(['last_verification_attempt' => now()]);
    }

    public function generateFileHash(): string
    {
        if (file_exists(storage_path('app/' . $this->file_path))) {
            return hash_file('sha256', storage_path('app/' . $this->file_path));
        }
        return '';
    }

    public function verifyFileIntegrity(): bool
    {
        return $this->file_hash === $this->generateFileHash();
    }
}
