<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * HolidaySchedule aliases the special_schedules table entries with type='holiday'.
 * It enables dedicated queries for holiday scheduling without duplicating storage.
 */
class HolidaySchedule extends Model
{
    use HasFactory;

    protected $table = 'special_schedules';

    protected $fillable = [
        'name',
        'date',
        'type',
        'priority',
        'applies_to',
        'custom_timings',
        'is_active',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
        'custom_timings' => 'array',
    ];

    // Scope to only holiday entries
    public function scopeHoliday($query)
    {
        return $query->where('type', 'holiday');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get today's holiday schedule if present.
     */
    public static function getTodayHoliday()
    {
        return static::holiday()
            ->active()
            ->whereDate('date', now()->toDateString())
            ->first();
    }
}
