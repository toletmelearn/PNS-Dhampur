<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'is_current',
        'description',
        'settings'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_current' => 'boolean',
        'settings' => 'array'
    ];

    /**
     * Get the current academic year
     */
    public static function current()
    {
        return Cache::remember('current_academic_year', 3600, function () {
            return static::where('is_current', true)->first();
        });
    }

    /**
     * Get all active academic years
     */
    public static function active()
    {
        return static::where('is_active', true)->orderBy('start_date', 'desc')->get();
    }

    /**
     * Set as current academic year
     */
    public function setCurrent()
    {
        // Remove current flag from all other years
        static::where('is_current', true)->update(['is_current' => false]);
        
        // Set this year as current
        $this->update(['is_current' => true, 'is_active' => true]);
        
        // Clear cache
        Cache::forget('current_academic_year');
        
        return $this;
    }

    /**
     * Check if this academic year is currently active (within date range)
     */
    public function isInSession()
    {
        $now = Carbon::now()->toDateString();
        return $now >= $this->start_date && $now <= $this->end_date;
    }

    /**
     * Get academic year progress percentage
     */
    public function getProgressPercentage()
    {
        $now = Carbon::now();
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        
        if ($now < $start) {
            return 0;
        }
        
        if ($now > $end) {
            return 100;
        }
        
        $totalDays = $end->diffInDays($start);
        $passedDays = $now->diffInDays($start);
        
        return round(($passedDays / $totalDays) * 100, 2);
    }

    /**
     * Get remaining days in academic year
     */
    public function getRemainingDays()
    {
        $now = Carbon::now();
        $end = Carbon::parse($this->end_date);
        
        if ($now > $end) {
            return 0;
        }
        
        return $now->diffInDays($end);
    }

    /**
     * Scope for academic years that overlap with given date range
     */
    public function scopeOverlapping($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Get holidays for this academic year
     */
    public function holidays()
    {
        return $this->hasMany(Holiday::class);
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($academicYear) {
            Cache::forget('current_academic_year');
        });

        static::deleted(function ($academicYear) {
            Cache::forget('current_academic_year');
        });
    }

    /**
     * Generate academic year name from dates
     */
    public static function generateName($startDate, $endDate)
    {
        $startYear = Carbon::parse($startDate)->year;
        $endYear = Carbon::parse($endDate)->year;
        
        return "{$startYear}-{$endYear}";
    }

    /**
     * Create a new academic year with default settings
     */
    public static function createWithDefaults($startDate, $endDate, $description = null)
    {
        $name = static::generateName($startDate, $endDate);
        
        return static::create([
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => $description,
            'is_active' => true,
            'is_current' => false,
            'settings' => [
                'terms' => [
                    ['name' => 'First Term', 'start_date' => $startDate, 'end_date' => null],
                    ['name' => 'Second Term', 'start_date' => null, 'end_date' => null],
                    ['name' => 'Third Term', 'start_date' => null, 'end_date' => $endDate]
                ],
                'grading_system' => 'percentage',
                'pass_marks' => 40
            ]
        ]);
    }
}