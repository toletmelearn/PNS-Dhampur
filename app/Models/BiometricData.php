<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BiometricData extends Model
{
    use HasFactory;

    protected $table = 'biometric_data';

    protected $fillable = [
        'teacher_id',
        'device_id',
        'biometric_type',
        'biometric_template',
        'template_format',
        'template_quality',
        'device_info',
        'enrolled_at',
        'enrolled_by',
        'is_active',
        'last_used_at',
        'usage_count'
    ];

    protected $casts = [
        'device_info' => 'array',
        'enrolled_at' => 'datetime',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
        'template_quality' => 'integer',
        'usage_count' => 'integer'
    ];

    protected $dates = [
        'enrolled_at',
        'last_used_at',
        'created_at',
        'updated_at'
    ];

    // Biometric types
    const BIOMETRIC_TYPES = [
        'fingerprint' => 'Fingerprint',
        'face' => 'Face Recognition',
        'iris' => 'Iris Scan',
        'palm' => 'Palm Print',
        'voice' => 'Voice Recognition'
    ];

    // Template formats
    const TEMPLATE_FORMATS = [
        'iso' => 'ISO Standard',
        'ansi' => 'ANSI Standard',
        'proprietary' => 'Proprietary Format'
    ];

    /**
     * Get the teacher that owns this biometric data
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the user who enrolled this biometric data
     */
    public function enrolledBy()
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    /**
     * Get the device associated with this biometric data
     */
    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'device_id', 'device_id');
    }

    /**
     * Scope for active biometric data
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific biometric type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('biometric_type', $type);
    }

    /**
     * Scope for specific device
     */
    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Update usage statistics
     */
    public function recordUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if template is expired (based on enrollment date)
     */
    public function isExpired($months = 12): bool
    {
        return $this->enrolled_at->addMonths($months)->isPast();
    }

    /**
     * Get quality score as percentage
     */
    public function getQualityPercentageAttribute(): ?float
    {
        return $this->template_quality ? ($this->template_quality / 100) * 100 : null;
    }

    /**
     * Get formatted biometric type
     */
    public function getFormattedTypeAttribute(): string
    {
        return self::BIOMETRIC_TYPES[$this->biometric_type] ?? ucfirst($this->biometric_type);
    }

    /**
     * Get formatted template format
     */
    public function getFormattedFormatAttribute(): string
    {
        return self::TEMPLATE_FORMATS[$this->template_format] ?? ucfirst($this->template_format);
    }

    /**
     * Check if biometric data needs re-enrollment
     */
    public function needsReEnrollment(): bool
    {
        return $this->isExpired() || 
               ($this->template_quality && $this->template_quality < 70) ||
               !$this->is_active;
    }
}