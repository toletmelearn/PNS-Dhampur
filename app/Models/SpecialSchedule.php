<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SpecialSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'date',
        'schedule_type',
        'custom_timings',
        'is_active',
        'created_by',
        'applies_to',
        'notification_message',
        'priority'
    ];

    protected $casts = [
        'date' => 'date',
        'custom_timings' => 'array',
        'is_active' => 'boolean',
        'applies_to' => 'array'
    ];

    /**
     * Relationship with User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if there's a special schedule for today
     */
    public static function getTodaySpecialSchedule()
    {
        return self::where('date', Carbon::today())
                   ->where('is_active', true)
                   ->orderBy('priority', 'desc')
                   ->first();
    }

    /**
     * Get upcoming special schedules
     */
    public static function getUpcomingSchedules($days = 7)
    {
        return self::where('date', '>=', Carbon::today())
                   ->where('date', '<=', Carbon::today()->addDays($days))
                   ->where('is_active', true)
                   ->orderBy('date')
                   ->orderBy('priority', 'desc')
                   ->get();
    }

    /**
     * Check if today has a special schedule
     */
    public static function hasTodaySpecialSchedule()
    {
        return self::where('date', Carbon::today())
                   ->where('is_active', true)
                   ->exists();
    }

    /**
     * Get effective schedule for today (special or regular)
     */
    public static function getEffectiveSchedule()
    {
        $specialSchedule = self::getTodaySpecialSchedule();
        
        if ($specialSchedule && $specialSchedule->custom_timings) {
            return [
                'type' => 'special',
                'schedule' => $specialSchedule,
                'timings' => $specialSchedule->custom_timings
            ];
        }
        
        return [
            'type' => 'regular',
            'schedule' => null,
            'timings' => BellTiming::getCurrentSchedule()
        ];
    }

    /**
     * Create predefined special schedules
     */
    public static function createPredefinedSchedules()
    {
        $schedules = [
            [
                'name' => 'Half Day Schedule',
                'description' => 'Shortened periods for half day',
                'schedule_type' => 'half_day',
                'custom_timings' => [
                    ['name' => 'Period 1', 'time' => '08:00', 'type' => 'start'],
                    ['name' => 'Period 1 End', 'time' => '08:30', 'type' => 'end'],
                    ['name' => 'Period 2', 'time' => '08:30', 'type' => 'start'],
                    ['name' => 'Period 2 End', 'time' => '09:00', 'type' => 'end'],
                    ['name' => 'Break', 'time' => '09:00', 'type' => 'break'],
                    ['name' => 'Period 3', 'time' => '09:15', 'type' => 'start'],
                    ['name' => 'Period 3 End', 'time' => '09:45', 'type' => 'end'],
                    ['name' => 'Period 4', 'time' => '09:45', 'type' => 'start'],
                    ['name' => 'Dismissal', 'time' => '10:15', 'type' => 'end']
                ],
                'applies_to' => ['all'],
                'priority' => 'high'
            ],
            [
                'name' => 'Exam Schedule',
                'description' => 'Extended periods for examinations',
                'schedule_type' => 'exam',
                'custom_timings' => [
                    ['name' => 'Exam Session 1', 'time' => '08:00', 'type' => 'start'],
                    ['name' => 'Exam Break', 'time' => '10:00', 'type' => 'break'],
                    ['name' => 'Exam Session 2', 'time' => '10:30', 'type' => 'start'],
                    ['name' => 'Lunch Break', 'time' => '12:30', 'type' => 'break'],
                    ['name' => 'Exam Session 3', 'time' => '13:30', 'type' => 'start'],
                    ['name' => 'Dismissal', 'time' => '15:30', 'type' => 'end']
                ],
                'applies_to' => ['students', 'teachers'],
                'priority' => 'high'
            ]
        ];

        return $schedules;
    }
}