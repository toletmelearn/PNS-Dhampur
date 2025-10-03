<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BellNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'bell_timing_id',
        'notification_type',
        'title',
        'message',
        'sound_file',
        'is_enabled',
        'notification_time',
        'target_audience',
        'priority',
        'auto_dismiss',
        'dismiss_after_seconds'
    ];

    protected $casts = [
        'notification_time' => 'datetime',
        'is_enabled' => 'boolean',
        'auto_dismiss' => 'boolean',
        'target_audience' => 'array'
    ];

    /**
     * Relationship with BellTiming
     */
    public function bellTiming()
    {
        return $this->belongsTo(BellTiming::class);
    }

    /**
     * Get notifications that should be triggered now
     */
    public static function getActiveNotifications()
    {
        $currentTime = Carbon::now();
        $season = BellTiming::getCurrentSeason();
        
        return self::whereHas('bellTiming', function($query) use ($season) {
                    $query->where('season', $season)
                          ->where('is_active', true);
                })
                ->where('is_enabled', true)
                ->whereTime('notification_time', '<=', $currentTime->format('H:i:s'))
                ->whereTime('notification_time', '>=', $currentTime->subMinutes(1)->format('H:i:s'))
                ->orderBy('priority', 'desc')
                ->get();
    }

    /**
     * Get upcoming notifications for the next hour
     */
    public static function getUpcomingNotifications()
    {
        $currentTime = Carbon::now();
        $nextHour = $currentTime->copy()->addHour();
        $season = BellTiming::getCurrentSeason();
        
        return self::whereHas('bellTiming', function($query) use ($season) {
                    $query->where('season', $season)
                          ->where('is_active', true);
                })
                ->where('is_enabled', true)
                ->whereTime('notification_time', '>', $currentTime->format('H:i:s'))
                ->whereTime('notification_time', '<=', $nextHour->format('H:i:s'))
                ->orderBy('notification_time')
                ->get();
    }

    /**
     * Create default notifications for a bell timing
     */
    public static function createDefaultNotifications($bellTimingId)
    {
        $notifications = [
            [
                'bell_timing_id' => $bellTimingId,
                'notification_type' => 'visual',
                'title' => 'Bell Alert',
                'message' => 'It\'s time for the next period',
                'is_enabled' => true,
                'target_audience' => ['teachers', 'students', 'staff'],
                'priority' => 'medium',
                'auto_dismiss' => true,
                'dismiss_after_seconds' => 10
            ],
            [
                'bell_timing_id' => $bellTimingId,
                'notification_type' => 'audio',
                'title' => 'Bell Sound',
                'message' => 'Bell ringing',
                'sound_file' => 'bell-sound.mp3',
                'is_enabled' => true,
                'target_audience' => ['teachers', 'students', 'staff'],
                'priority' => 'high',
                'auto_dismiss' => true,
                'dismiss_after_seconds' => 5
            ],
            [
                'bell_timing_id' => $bellTimingId,
                'notification_type' => 'push',
                'title' => 'Period Change',
                'message' => 'Next period is starting',
                'is_enabled' => true,
                'target_audience' => ['teachers'],
                'priority' => 'medium',
                'auto_dismiss' => false
            ]
        ];

        foreach ($notifications as $notification) {
            self::create($notification);
        }
    }
}