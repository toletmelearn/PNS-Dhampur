<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'type',
        'category',
        'is_recurring',
        'recurrence_pattern',
        'is_active',
        'color',
        'academic_year_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean'
    ];

    /**
     * Holiday types
     */
    const TYPES = [
        'holiday' => 'Holiday',
        'event' => 'Event',
        'exam' => 'Examination',
        'vacation' => 'Vacation'
    ];

    /**
     * Holiday categories
     */
    const CATEGORIES = [
        'general' => 'General',
        'religious' => 'Religious',
        'national' => 'National',
        'school' => 'School Specific'
    ];

    /**
     * Recurrence patterns
     */
    const RECURRENCE_PATTERNS = [
        'yearly' => 'Yearly',
        'monthly' => 'Monthly',
        'weekly' => 'Weekly'
    ];

    /**
     * Get the academic year this holiday belongs to
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Scope for active holidays
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for holidays by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for holidays by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for holidays in date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
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
     * Scope for current holidays (happening today)
     */
    public function scopeCurrent($query)
    {
        $today = Carbon::today()->toDateString();
        return $query->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->where('is_active', true);
    }

    /**
     * Scope for upcoming holidays
     */
    public function scopeUpcoming($query, $days = 30)
    {
        $today = Carbon::today();
        $futureDate = $today->copy()->addDays($days);
        
        return $query->where('start_date', '>=', $today->toDateString())
                    ->where('start_date', '<=', $futureDate->toDateString())
                    ->where('is_active', true)
                    ->orderBy('start_date');
    }

    /**
     * Check if holiday is currently active (happening today)
     */
    public function isCurrentlyActive()
    {
        $today = Carbon::today();
        return $today->between($this->start_date, $this->end_date) && $this->is_active;
    }

    /**
     * Check if holiday is upcoming
     */
    public function isUpcoming()
    {
        return Carbon::today()->lt($this->start_date) && $this->is_active;
    }

    /**
     * Get duration in days
     */
    public function getDurationInDays()
    {
        return Carbon::parse($this->start_date)->diffInDays(Carbon::parse($this->end_date)) + 1;
    }

    /**
     * Check if holiday spans multiple days
     */
    public function isMultiDay()
    {
        return $this->start_date !== $this->end_date;
    }

    /**
     * Get formatted date range
     */
    public function getFormattedDateRange()
    {
        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        
        if ($this->isMultiDay()) {
            if ($start->year === $end->year && $start->month === $end->month) {
                return $start->format('M j') . ' - ' . $end->format('j, Y');
            } else {
                return $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
            }
        } else {
            return $start->format('M j, Y');
        }
    }

    /**
     * Get holidays for calendar display
     */
    public static function getForCalendar($startDate, $endDate, $academicYearId = null)
    {
        $query = static::active()
            ->inDateRange($startDate, $endDate);
            
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }
        
        return $query->get()->map(function ($holiday) {
            return [
                'id' => $holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->start_date->toDateString(),
                'end' => $holiday->end_date->addDay()->toDateString(), // FullCalendar expects exclusive end date
                'color' => $holiday->color,
                'description' => $holiday->description,
                'type' => $holiday->type,
                'category' => $holiday->category,
                'allDay' => true
            ];
        });
    }

    /**
     * Create recurring holidays for next year
     */
    public function createRecurringInstance($targetYear)
    {
        if (!$this->is_recurring) {
            return null;
        }

        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);
        
        switch ($this->recurrence_pattern) {
            case 'yearly':
                $newStartDate = $startDate->copy()->year($targetYear);
                $newEndDate = $endDate->copy()->year($targetYear);
                break;
            default:
                return null;
        }

        return static::create([
            'name' => $this->name,
            'description' => $this->description,
            'start_date' => $newStartDate,
            'end_date' => $newEndDate,
            'type' => $this->type,
            'category' => $this->category,
            'is_recurring' => $this->is_recurring,
            'recurrence_pattern' => $this->recurrence_pattern,
            'is_active' => $this->is_active,
            'color' => $this->color,
            'academic_year_id' => null // Will be set when assigned to academic year
        ]);
    }

    /**
     * Get type label
     */
    public function getTypeLabel()
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Get category label
     */
    public function getCategoryLabel()
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}