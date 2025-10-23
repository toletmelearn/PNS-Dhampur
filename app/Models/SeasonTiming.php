<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * SeasonTiming is an alias model over the bell_timings table
 * providing season-scoped queries and helpers.
 */
class SeasonTiming extends Model
{
    use HasFactory;

    protected $table = 'bell_timings';

    protected $fillable = [
        'name',
        'time',
        'season',
        'type',
        'description',
        'is_active',
        'order',
    ];

    protected $casts = [
        'time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeSeason($query, string $season)
    {
        return $query->where('season', $season);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('time');
    }

    /**
     * Get active timings for the current season.
     */
    public static function getCurrentSeasonTimings()
    {
        $season = BellTiming::getCurrentSeason();
        return static::season($season)->active()->ordered()->get();
    }

    /**
     * Check if it's time for a bell for current season.
     */
    public static function checkBellTime()
    {
        $season = BellTiming::getCurrentSeason();
        $currentTime = Carbon::now()->format('H:i');

        return static::season($season)
            ->active()
            ->whereTime('time', $currentTime)
            ->get();
    }
}
