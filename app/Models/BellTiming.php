<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BellTiming extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'time',
        'season',
        'type',
        'description',
        'is_active',
        'order'
    ];

    protected $casts = [
        'time' => 'datetime:H:i',
        'is_active' => 'boolean'
    ];

    /**
     * Get current season based on date
     */
    public static function getCurrentSeason()
    {
        $month = Carbon::now()->month;
        // Winter: November to February (11, 12, 1, 2)
        // Summer: March to October (3, 4, 5, 6, 7, 8, 9, 10)
        return in_array($month, [11, 12, 1, 2]) ? 'winter' : 'summer';
    }

    /**
     * Get active bell timings for current season
     */
    public static function getCurrentSchedule()
    {
        $season = self::getCurrentSeason();
        return self::where('season', $season)
                   ->where('is_active', true)
                   ->orderBy('order')
                   ->orderBy('time')
                   ->get();
    }

    /**
     * Get next bell timing
     */
    public static function getNextBell()
    {
        $season = self::getCurrentSeason();
        $currentTime = Carbon::now()->format('H:i:s');
        
        return self::where('season', $season)
                   ->where('is_active', true)
                   ->whereTime('time', '>', $currentTime)
                   ->orderBy('time')
                   ->first();
    }

    /**
     * Check if it's time for a bell
     */
    public static function checkBellTime()
    {
        $season = self::getCurrentSeason();
        $currentTime = Carbon::now()->format('H:i');
        
        return self::where('season', $season)
                   ->where('is_active', true)
                   ->whereTime('time', $currentTime)
                   ->get();
    }
}