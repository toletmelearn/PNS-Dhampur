<?php

namespace App\Services;

use App\Models\BellTiming;
use App\Models\SpecialSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SeasonSwitchingService
{
    /**
     * Season configuration with date ranges
     */
    private const SEASON_CONFIG = [
        'summer' => [
            'start_month' => 4,  // April
            'start_day' => 1,
            'end_month' => 9,    // September
            'end_day' => 30,
            'name' => 'Summer Schedule',
            'description' => 'Hot weather schedule with adjusted timings'
        ],
        'winter' => [
            'start_month' => 10, // October
            'start_day' => 1,
            'end_month' => 3,    // March
            'end_day' => 31,
            'name' => 'Winter Schedule',
            'description' => 'Cold weather schedule with standard timings'
        ]
    ];

    /**
     * Get the current season based on today's date
     */
    public function getCurrentSeason(): string
    {
        return $this->getSeasonForDate(Carbon::now());
    }

    /**
     * Get the season for a specific date
     */
    public function getSeasonForDate(Carbon $date): string
    {
        $month = $date->month;
        $day = $date->day;

        // Summer season: April 1 - September 30
        if (($month >= 4 && $month <= 9)) {
            return 'summer';
        }

        // Winter season: October 1 - March 31
        return 'winter';
    }

    /**
     * Check if season switching is needed and perform the switch
     */
    public function checkAndSwitchSeason(): array
    {
        $currentSeason = $this->getCurrentSeason();
        $lastKnownSeason = Cache::get('current_bell_season', null);

        $result = [
            'switched' => false,
            'from_season' => $lastKnownSeason,
            'to_season' => $currentSeason,
            'switch_date' => Carbon::now()->toDateTimeString(),
            'affected_schedules' => 0,
            'notifications_sent' => 0
        ];

        // If this is the first time or season has changed
        if ($lastKnownSeason !== $currentSeason) {
            $result['switched'] = true;
            
            // Update cache
            Cache::put('current_bell_season', $currentSeason, now()->addDays(30));
            
            // Switch active schedules
            $result['affected_schedules'] = $this->switchActiveSchedules($currentSeason);
            
            // Send notifications
            $result['notifications_sent'] = $this->sendSeasonSwitchNotifications($lastKnownSeason, $currentSeason);
            
            // Log the season switch
            Log::info('Season switched automatically', $result);
        }

        return $result;
    }

    /**
     * Switch active bell timing schedules based on season
     */
    private function switchActiveSchedules(string $season): int
    {
        $affectedCount = 0;

        try {
            // Deactivate all current schedules
            BellTiming::where('is_active', true)->update(['is_active' => false]);
            
            // Activate schedules for the new season
            $seasonSchedules = BellTiming::where('season', $season)->get();
            
            foreach ($seasonSchedules as $schedule) {
                $schedule->update(['is_active' => true]);
                $affectedCount++;
            }

            // If no season-specific schedules exist, activate default schedules
            if ($affectedCount === 0) {
                $defaultSchedules = BellTiming::whereNull('season')
                    ->orWhere('season', 'default')
                    ->get();
                
                foreach ($defaultSchedules as $schedule) {
                    $schedule->update(['is_active' => true]);
                    $affectedCount++;
                }
            }

        } catch (\Exception $e) {
            Log::error('Error switching season schedules: ' . $e->getMessage());
        }

        return $affectedCount;
    }

    /**
     * Send notifications about season switch
     */
    private function sendSeasonSwitchNotifications(string $fromSeason, string $toSeason): int
    {
        $notificationCount = 0;

        try {
            $seasonConfig = self::SEASON_CONFIG[$toSeason] ?? [];
            $message = sprintf(
                'Bell schedule has been automatically switched from %s to %s season. New timings are now active.',
                ucfirst($fromSeason ?? 'previous'),
                ucfirst($toSeason)
            );

            // Create system notification
            $notification = [
                'type' => 'season_switch',
                'title' => 'Season Schedule Changed',
                'message' => $message,
                'season_from' => $fromSeason,
                'season_to' => $toSeason,
                'timestamp' => Carbon::now()->toISOString(),
                'priority' => 'medium'
            ];

            // Store notification in cache for dashboard display
            $notifications = Cache::get('system_notifications', []);
            array_unshift($notifications, $notification);
            
            // Keep only last 50 notifications
            $notifications = array_slice($notifications, 0, 50);
            Cache::put('system_notifications', $notifications, now()->addDays(7));

            $notificationCount++;

            // Send push notifications if mobile service is available
            if (class_exists('\App\Services\PushNotificationService')) {
                $pushService = app(\App\Services\PushNotificationService::class);
                $pushService->sendToAll([
                    'title' => 'Season Schedule Changed',
                    'body' => $message,
                    'icon' => '/images/bell-icon.png',
                    'badge' => '/images/badge-icon.png',
                    'data' => [
                        'type' => 'season_switch',
                        'season' => $toSeason,
                        'url' => '/bell-schedule/dashboard'
                    ]
                ]);
                $notificationCount++;
            }

        } catch (\Exception $e) {
            Log::error('Error sending season switch notifications: ' . $e->getMessage());
        }

        return $notificationCount;
    }

    /**
     * Get season information
     */
    public function getSeasonInfo(string $season = null): array
    {
        $season = $season ?? $this->getCurrentSeason();
        $config = self::SEASON_CONFIG[$season] ?? [];

        return [
            'season' => $season,
            'name' => $config['name'] ?? ucfirst($season) . ' Schedule',
            'description' => $config['description'] ?? '',
            'is_current' => $season === $this->getCurrentSeason(),
            'date_range' => $this->getSeasonDateRange($season),
            'next_switch' => $this->getNextSeasonSwitchDate()
        ];
    }

    /**
     * Get date range for a season
     */
    private function getSeasonDateRange(string $season): array
    {
        $config = self::SEASON_CONFIG[$season] ?? [];
        
        if (empty($config)) {
            return [];
        }

        $currentYear = Carbon::now()->year;
        
        // Handle winter season that spans across years
        if ($season === 'winter') {
            $startDate = Carbon::create($currentYear, $config['start_month'], $config['start_day']);
            $endDate = Carbon::create($currentYear + 1, $config['end_month'], $config['end_day']);
            
            // If we're in the first quarter of the year, adjust the year
            if (Carbon::now()->month <= 3) {
                $startDate = Carbon::create($currentYear - 1, $config['start_month'], $config['start_day']);
                $endDate = Carbon::create($currentYear, $config['end_month'], $config['end_day']);
            }
        } else {
            $startDate = Carbon::create($currentYear, $config['start_month'], $config['start_day']);
            $endDate = Carbon::create($currentYear, $config['end_month'], $config['end_day']);
        }

        return [
            'start' => $startDate->toDateString(),
            'end' => $endDate->toDateString(),
            'start_formatted' => $startDate->format('M j'),
            'end_formatted' => $endDate->format('M j')
        ];
    }

    /**
     * Get the next season switch date
     */
    public function getNextSeasonSwitchDate(): array
    {
        $currentSeason = $this->getCurrentSeason();
        $now = Carbon::now();
        
        if ($currentSeason === 'summer') {
            // Next switch is to winter (October 1)
            $nextSwitch = Carbon::create($now->year, 10, 1);
            if ($nextSwitch->isPast()) {
                $nextSwitch = Carbon::create($now->year + 1, 10, 1);
            }
            $nextSeason = 'winter';
        } else {
            // Next switch is to summer (April 1)
            $nextSwitch = Carbon::create($now->year, 4, 1);
            if ($nextSwitch->isPast()) {
                $nextSwitch = Carbon::create($now->year + 1, 4, 1);
            }
            $nextSeason = 'summer';
        }

        return [
            'date' => $nextSwitch->toDateString(),
            'formatted' => $nextSwitch->format('M j, Y'),
            'days_until' => $now->diffInDays($nextSwitch),
            'season' => $nextSeason
        ];
    }

    /**
     * Manually switch to a specific season
     */
    public function manualSeasonSwitch(string $season): array
    {
        if (!array_key_exists($season, self::SEASON_CONFIG)) {
            throw new \InvalidArgumentException("Invalid season: {$season}");
        }

        $currentSeason = Cache::get('current_bell_season', $this->getCurrentSeason());
        
        // Update cache
        Cache::put('current_bell_season', $season, now()->addDays(30));
        Cache::put('manual_season_override', true, now()->addDays(30));
        
        // Switch schedules
        $affectedSchedules = $this->switchActiveSchedules($season);
        
        // Send notifications
        $notificationsSent = $this->sendSeasonSwitchNotifications($currentSeason, $season);
        
        $result = [
            'switched' => true,
            'manual' => true,
            'from_season' => $currentSeason,
            'to_season' => $season,
            'switch_date' => Carbon::now()->toDateTimeString(),
            'affected_schedules' => $affectedSchedules,
            'notifications_sent' => $notificationsSent
        ];

        Log::info('Season switched manually', $result);
        
        return $result;
    }

    /**
     * Clear manual season override
     */
    public function clearManualOverride(): void
    {
        Cache::forget('manual_season_override');
        Cache::put('current_bell_season', $this->getCurrentSeason(), now()->addDays(30));
    }

    /**
     * Check if there's a manual season override
     */
    public function hasManualOverride(): bool
    {
        return Cache::has('manual_season_override');
    }
}