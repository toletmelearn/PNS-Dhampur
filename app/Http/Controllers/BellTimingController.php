<?php

namespace App\Http\Controllers;

use App\Models\BellTiming;
use App\Models\BellNotification;
use App\Models\SpecialSchedule;
use App\Models\Holiday;
use App\Models\BellLog;
use App\Services\SeasonSwitchingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class BellTimingController extends Controller
{
    /**
     * Display a listing of bell timings
     */
    public function index(Request $request): JsonResponse
    {
        $season = $request->get('season', BellTiming::getCurrentSeason());
        
        $bellTimings = BellTiming::where('season', $season)
                                ->orderBy('order')
                                ->orderBy('time')
                                ->get();

        return response()->json([
            'success' => true,
            'data' => $bellTimings,
            'current_season' => BellTiming::getCurrentSeason(),
            'special_schedule' => SpecialSchedule::getTodaySpecialSchedule()
        ]);
    }

    /**
     * Store a newly created bell timing
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'time' => 'required|date_format:H:i',
            'season' => ['required', Rule::in(['winter', 'summer'])],
            'type' => ['required', Rule::in(['start', 'end', 'break'])],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'order' => 'integer|min:0'
        ]);

        $bellTiming = BellTiming::create($validated);

        // Create default notifications for the new bell timing
        BellNotification::createDefaultNotifications($bellTiming->id);

        return response()->json([
            'success' => true,
            'message' => 'Bell timing created successfully',
            'data' => $bellTiming
        ], 201);
    }

    /**
     * Display the specified bell timing
     */
    public function show(BellTiming $bellTiming): JsonResponse
    {
        $bellTiming->load('notifications');
        
        return response()->json([
            'success' => true,
            'data' => $bellTiming
        ]);
    }

    /**
     * Update the specified bell timing
     */
    public function update(Request $request, BellTiming $bellTiming): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'time' => 'sometimes|required|date_format:H:i',
            'season' => ['sometimes', 'required', Rule::in(['winter', 'summer'])],
            'type' => ['sometimes', 'required', Rule::in(['start', 'end', 'break'])],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'order' => 'integer|min:0'
        ]);

        $bellTiming->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bell timing updated successfully',
            'data' => $bellTiming
        ]);
    }

    /**
     * Remove the specified bell timing
     */
    public function destroy(BellTiming $bellTiming): JsonResponse
    {
        $bellTiming->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bell timing deleted successfully'
        ]);
    }

    /**
     * Get current schedule with enhanced real-time information
     */
    public function getCurrentScheduleEnhanced(): JsonResponse
    {
        try {
            $currentSeason = BellTiming::getCurrentSeason();
            $currentTime = Carbon::now();
            
            // Get today's bell timings
            $bellTimings = BellTiming::where('season', $currentSeason)
                ->where('is_active', true)
                ->orderBy('time')
                ->get();
            
            // Find current and next periods
            $currentPeriod = null;
            $nextPeriod = null;
            $periodProgress = 0;
            
            foreach ($bellTimings as $index => $timing) {
                $timingTime = Carbon::createFromFormat('H:i:s', $timing->time);
                $timingDateTime = $currentTime->copy()->setTime($timingTime->hour, $timingTime->minute, $timingTime->second);
                
                if ($currentTime->gte($timingDateTime)) {
                    $currentPeriod = $timing;
                    
                    // Calculate progress if there's a next timing
                    if (isset($bellTimings[$index + 1])) {
                        $nextTiming = $bellTimings[$index + 1];
                        $nextTimingTime = Carbon::createFromFormat('H:i:s', $nextTiming->time);
                        $nextTimingDateTime = $currentTime->copy()->setTime($nextTimingTime->hour, $nextTimingTime->minute, $nextTimingTime->second);
                        
                        $totalDuration = $nextTimingDateTime->diffInMinutes($timingDateTime);
                        $elapsed = $currentTime->diffInMinutes($timingDateTime);
                        $periodProgress = $totalDuration > 0 ? min(100, ($elapsed / $totalDuration) * 100) : 0;
                        
                        $nextPeriod = $nextTiming;
                    }
                } else {
                    if (!$nextPeriod) {
                        $nextPeriod = $timing;
                    }
                    break;
                }
            }
            
            // Calculate time remaining to next period
            $timeRemaining = null;
            if ($nextPeriod) {
                $nextTimingTime = Carbon::createFromFormat('H:i:s', $nextPeriod->time);
                $nextTimingDateTime = $currentTime->copy()->setTime($nextTimingTime->hour, $nextTimingTime->minute, $nextTimingTime->second);
                
                if ($nextTimingDateTime->lt($currentTime)) {
                    $nextTimingDateTime->addDay();
                }
                
                $timeRemaining = $currentTime->diffInMinutes($nextTimingDateTime);
            }
            
            // Get upcoming notifications
            $upcomingNotifications = BellNotification::where('is_active', true)
                ->where('bell_timing_id', $nextPeriod ? $nextPeriod->id : null)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current_time' => $currentTime->format('H:i:s'),
                    'current_date' => $currentTime->format('Y-m-d'),
                    'current_season' => $currentSeason,
                    'current_period' => $currentPeriod ? [
                        'id' => $currentPeriod->id,
                        'name' => $currentPeriod->name,
                        'time' => $currentPeriod->time,
                        'type' => $currentPeriod->type,
                        'progress' => round($periodProgress, 1)
                    ] : null,
                    'next_period' => $nextPeriod ? [
                        'id' => $nextPeriod->id,
                        'name' => $nextPeriod->name,
                        'time' => $nextPeriod->time,
                        'type' => $nextPeriod->type,
                        'time_remaining_minutes' => $timeRemaining
                    ] : null,
                    'bell_timings' => $bellTimings->map(function ($timing) use ($currentTime) {
                        $timingTime = Carbon::createFromFormat('H:i:s', $timing->time);
                        $timingDateTime = $currentTime->copy()->setTime($timingTime->hour, $timingTime->minute, $timingTime->second);
                        
                        return [
                            'id' => $timing->id,
                            'name' => $timing->name,
                            'time' => $timing->time,
                            'type' => $timing->type,
                            'is_active' => $timing->is_active,
                            'is_current' => $currentTime->gte($timingDateTime),
                            'status' => $currentTime->gte($timingDateTime) ? 'completed' : 'upcoming'
                        ];
                    }),
                    'upcoming_notifications' => $upcomingNotifications,
                    'is_school_hours' => $this->isSchoolHours($currentTime, $bellTimings)
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getting enhanced current schedule: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving schedule data'
            ], 500);
        }
    }
    
    /**
     * Check if current time is within school hours
     */
    private function isSchoolHours($currentTime, $bellTimings)
    {
        if ($bellTimings->isEmpty()) {
            return false;
        }
        
        $firstBell = $bellTimings->first();
        $lastBell = $bellTimings->last();
        
        $firstTime = Carbon::createFromFormat('H:i:s', $firstBell->time);
        $lastTime = Carbon::createFromFormat('H:i:s', $lastBell->time);
        
        $firstDateTime = $currentTime->copy()->setTime($firstTime->hour, $firstTime->minute, $firstTime->second);
        $lastDateTime = $currentTime->copy()->setTime($lastTime->hour, $lastTime->minute, $lastTime->second);
        
        return $currentTime->between($firstDateTime, $lastDateTime);
    }

    /**
     * Get current schedule for today
     */
    public function getCurrentSchedule(): JsonResponse
    {
        $effectiveSchedule = SpecialSchedule::getEffectiveSchedule();
        $nextBell = BellTiming::getNextBell();
        $upcomingNotifications = BellNotification::getUpcomingNotifications();

        return response()->json([
            'success' => true,
            'data' => [
                'effective_schedule' => $effectiveSchedule,
                'next_bell' => $nextBell,
                'current_season' => BellTiming::getCurrentSeason(),
                'upcoming_notifications' => $upcomingNotifications,
                'current_time' => Carbon::now()->format('H:i:s'),
                'current_date' => Carbon::now()->format('Y-m-d')
            ]
        ]);
    }

    /**
     * Check for bell notifications
     */
    public function checkBellNotification(): JsonResponse
    {
        $todaySpecial = SpecialSchedule::getTodaySpecialSchedule();
        $isHolidayToday = Holiday::current()->exists();

        // Suppress ringing on holidays unless a special schedule is active
        if ($isHolidayToday && !$todaySpecial) {
            $currentTimeStr = Carbon::now()->format('H:i');
            BellLog::create([
                'bell_timing_id' => null,
                'special_schedule_id' => null,
                'schedule_type' => 'regular',
                'ring_type' => 'auto',
                'name' => 'Holiday - No Bells',
                'time' => $currentTimeStr,
                'season' => BellTiming::getCurrentSeason(),
                'date' => now()->toDateString(),
                'suppressed' => true,
                'forced' => false,
                'reason' => 'holiday',
                'metadata' => null,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'bells_to_ring' => [],
                    'should_ring' => false,
                    'active_notifications' => [],
                    'has_notifications' => false,
                    'suppressed' => true,
                    'suppression_reason' => 'holiday'
                ]
            ]);
        }

        $currentTimeStr = Carbon::now()->format('H:i');
        $bellsToRing = [];

        if ($todaySpecial && is_array($todaySpecial->custom_timings) && count($todaySpecial->custom_timings) > 0) {
            foreach ($todaySpecial->custom_timings as $timing) {
                $timingStr = $timing['time'] ?? null;
                if ($timingStr && $timingStr === $currentTimeStr) {
                    $bellsToRing[] = [
                        'id' => null,
                        'name' => $timing['name'] ?? $todaySpecial->name,
                        'time' => $timingStr,
                        'type' => $timing['type'] ?? 'start',
                        'description' => $todaySpecial->description ?? 'Special schedule bell',
                        'season' => BellTiming::getCurrentSeason(),
                        'special_schedule' => true
                    ];
                }
            }
        } else {
            // Regular schedule fallback
            $bellsToRing = BellTiming::checkBellTime()->map(function($bell) {
                return [
                    'id' => $bell->id,
                    'name' => $bell->name,
                    'time' => \Carbon\Carbon::parse($bell->time)->format('H:i'),
                    'type' => $bell->type,
                    'description' => $bell->description,
                    'season' => $bell->season,
                    'special_schedule' => false
                ];
            })->values()->all();
        }

        $activeNotifications = BellNotification::getActiveNotifications();

        // Log bell ring events
        foreach ($bellsToRing as $entry) {
            BellLog::create([
                'bell_timing_id' => $entry['id'],
                'special_schedule_id' => ($entry['special_schedule'] && $todaySpecial) ? $todaySpecial->id : null,
                'schedule_type' => $entry['special_schedule'] ? 'special' : 'regular',
                'ring_type' => 'auto',
                'name' => $entry['name'],
                'time' => $entry['time'],
                'season' => $entry['season'] ?? BellTiming::getCurrentSeason(),
                'date' => now()->toDateString(),
                'suppressed' => false,
                'forced' => false,
                'reason' => null,
                'metadata' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'bells_to_ring' => $bellsToRing,
                'should_ring' => count($bellsToRing) > 0,
                'active_notifications' => $activeNotifications,
                'has_notifications' => $activeNotifications->count() > 0,
                'special_schedule_active' => (bool) $todaySpecial
            ]
        ]);
    }

    /**
     * Toggle bell timing active status
     */
    public function toggleActive(BellTiming $bellTiming): JsonResponse
    {
        $bellTiming->update(['is_active' => !$bellTiming->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Bell timing status updated successfully',
            'data' => $bellTiming
        ]);
    }

    /**
     * Bulk update bell timings order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bell_timings' => 'required|array',
            'bell_timings.*.id' => 'required|exists:bell_timings,id',
            'bell_timings.*.order' => 'required|integer|min:0'
        ]);

        foreach ($validated['bell_timings'] as $bellData) {
            BellTiming::where('id', $bellData['id'])
                     ->update(['order' => $bellData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bell timing order updated successfully'
        ]);
    }

    /**
     * Get bell schedule dashboard
     */
    public function dashboard(): JsonResponse
    {
        $currentSchedule = SpecialSchedule::getEffectiveSchedule();
        $upcomingSchedules = SpecialSchedule::getUpcomingSchedules();
        $nextBell = BellTiming::getNextBell();
        $currentTime = Carbon::now();

        return response()->json([
            'success' => true,
            'data' => [
                'current_schedule' => $currentSchedule,
                'upcoming_schedules' => $upcomingSchedules,
                'next_bell' => $nextBell,
                'current_time' => $currentTime->format('H:i:s'),
                'current_date' => $currentTime->format('Y-m-d'),
                'current_season' => BellTiming::getCurrentSeason(),
                'time_until_next_bell' => $nextBell ? $currentTime->diffInMinutes(Carbon::parse($nextBell->time)) : null
            ]
        ]);
    }

    /**
     * Get notification settings for a bell timing
     */
    public function getNotifications(BellTiming $bellTiming): JsonResponse
    {
        $notifications = $bellTiming->notifications()->orderBy('priority', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request, BellTiming $bellTiming): JsonResponse
    {
        $validated = $request->validate([
            'notifications' => 'required|array',
            'notifications.*.id' => 'sometimes|exists:bell_notifications,id',
            'notifications.*.notification_type' => 'required|in:visual,audio,push,email,sms',
            'notifications.*.title' => 'required|string|max:255',
            'notifications.*.message' => 'required|string',
            'notifications.*.is_enabled' => 'boolean',
            'notifications.*.priority' => 'required|in:low,medium,high,urgent',
            'notifications.*.auto_dismiss' => 'boolean',
            'notifications.*.dismiss_after_seconds' => 'integer|min:1|max:300'
        ]);

        foreach ($validated['notifications'] as $notificationData) {
            if (isset($notificationData['id'])) {
                // Update existing notification
                BellNotification::where('id', $notificationData['id'])
                               ->where('bell_timing_id', $bellTiming->id)
                               ->update($notificationData);
            } else {
                // Create new notification
                $notificationData['bell_timing_id'] = $bellTiming->id;
                BellNotification::create($notificationData);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications updated successfully'
        ]);
    }

    /**
     * Update the current season for bell timings
     */
    public function updateSeason(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'season' => ['required', Rule::in(['winter', 'summer'])],
            'effective_date' => 'nullable|date|after_or_equal:today'
        ]);

        $season = $validated['season'];
        $effectiveDate = $validated['effective_date'] ?? now()->format('Y-m-d');

        // Check if there are bell timings for the requested season
        $seasonTimings = BellTiming::where('season', $season)->count();
        
        if ($seasonTimings === 0) {
            return response()->json([
                'success' => false,
                'message' => "No bell timings found for {$season} season. Please create timings first."
            ], 400);
        }

        // Deactivate all current active timings
        BellTiming::where('is_active', true)->update(['is_active' => false]);

        // Activate timings for the new season
        BellTiming::where('season', $season)->update(['is_active' => true]);

        // Log the season change
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'old_season' => BellTiming::getCurrentSeason(),
                'new_season' => $season,
                'effective_date' => $effectiveDate
            ])
            ->log('Bell timing season changed');

        return response()->json([
            'success' => true,
            'message' => "Season updated to {$season} successfully",
            'data' => [
                'current_season' => $season,
                'effective_date' => $effectiveDate,
                'active_timings_count' => BellTiming::where('season', $season)->where('is_active', true)->count()
            ]
        ]);
    }

    /**
     * Get the active schedule for the current season
     */
    public function getActiveSchedule(): JsonResponse
    {
        $currentSeason = BellTiming::getCurrentSeason();
        $currentTime = Carbon::now();
        $today = $currentTime->format('Y-m-d');

        // Check for special schedule first
        $specialSchedule = SpecialSchedule::getTodaySpecialSchedule();
        
        if ($specialSchedule) {
            $activeTimings = $specialSchedule->bellTimings()
                                           ->where('is_active', true)
                                           ->orderBy('order')
                                           ->orderBy('time')
                                           ->get();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'schedule_type' => 'special',
                    'schedule_name' => $specialSchedule->name,
                    'schedule_description' => $specialSchedule->description,
                    'timings' => $activeTimings,
                    'current_season' => $currentSeason,
                    'date' => $today
                ]
            ]);
        }

        // Get regular season schedule
        $activeTimings = BellTiming::where('season', $currentSeason)
                                  ->where('is_active', true)
                                  ->orderBy('order')
                                  ->orderBy('time')
                                  ->get();

        // Find current and next bell
        $currentBell = null;
        $nextBell = null;
        $currentTimeString = $currentTime->format('H:i');

        foreach ($activeTimings as $timing) {
            if ($timing->time <= $currentTimeString) {
                $currentBell = $timing;
            } elseif (!$nextBell && $timing->time > $currentTimeString) {
                $nextBell = $timing;
                break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'schedule_type' => 'regular',
                'timings' => $activeTimings,
                'current_bell' => $currentBell,
                'next_bell' => $nextBell,
                'current_season' => $currentSeason,
                'current_time' => $currentTimeString,
                'date' => $today,
                'total_periods' => $activeTimings->where('type', 'start')->count(),
                'time_until_next_bell' => $nextBell ? 
                    $currentTime->diffInMinutes(Carbon::parse($nextBell->time)) : null
            ]
        ]);
    }

    /**
     * Get season information
     */
    public function getSeasonInfo(Request $request): JsonResponse
    {
        $seasonService = app(SeasonSwitchingService::class);
        $season = $request->get('season');
        
        $seasonInfo = $seasonService->getSeasonInfo($season);
        
        return response()->json([
            'success' => true,
            'data' => $seasonInfo
        ]);
    }

    /**
     * Switch to a specific season manually
     */
    public function switchSeason(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'season' => ['required', Rule::in(['summer', 'winter'])]
        ]);

        $seasonService = app(SeasonSwitchingService::class);
        
        try {
            $result = $seasonService->manualSeasonSwitch($validated['season']);
            
            return response()->json([
                'success' => true,
                'message' => 'Season switched successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to switch season: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear manual season override
     */
    public function clearSeasonOverride(): JsonResponse
    {
        $seasonService = app(SeasonSwitchingService::class);
        $seasonService->clearManualOverride();
        
        return response()->json([
            'success' => true,
            'message' => 'Manual season override cleared'
        ]);
    }

    /**
     * Check and perform automatic season switching
     */
    public function checkSeasonSwitch(): JsonResponse
    {
        $seasonService = app(SeasonSwitchingService::class);
        
        try {
            $result = $seasonService->checkAndSwitchSeason();
            
            return response()->json([
                'success' => true,
                'message' => $result['switched'] ? 'Season switched successfully' : 'No season switch needed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check season switch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually trigger a bell ring now
     */
    public function ringNow(Request $request): JsonResponse
    {
        $force = (bool) $request->boolean('force', false);
        $name = $request->input('name', 'Manual Bell');
        $type = $request->input('type', 'start');
        $description = $request->input('description', 'Manual ring triggered');

        $todaySpecial = SpecialSchedule::getTodaySpecialSchedule();
        $isHolidayToday = Holiday::current()->exists();

        if ($isHolidayToday && !$todaySpecial && !$force) {
            // Log suppressed manual attempt
            BellLog::create([
                'bell_timing_id' => null,
                'special_schedule_id' => null,
                'schedule_type' => 'regular',
                'ring_type' => 'manual',
                'name' => $name,
                'time' => Carbon::now()->format('H:i'),
                'season' => BellTiming::getCurrentSeason(),
                'date' => now()->toDateString(),
                'suppressed' => true,
                'forced' => false,
                'reason' => 'holiday',
                'metadata' => null,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'bells_to_ring' => [],
                    'should_ring' => false,
                    'active_notifications' => [],
                    'has_notifications' => false,
                    'suppressed' => true,
                    'suppression_reason' => 'holiday'
                ]
            ]);
        }

        $bell = [
            'id' => null,
            'name' => $name,
            'time' => Carbon::now()->format('H:i'),
            'type' => $type,
            'description' => $description,
            'season' => BellTiming::getCurrentSeason(),
            'special_schedule' => (bool) $todaySpecial,
            'manual' => true
        ];

        $activeNotifications = BellNotification::getActiveNotifications();

        // Log manual ring
        BellLog::create([
            'bell_timing_id' => null,
            'special_schedule_id' => $todaySpecial ? $todaySpecial->id : null,
            'schedule_type' => $todaySpecial ? 'special' : 'regular',
            'ring_type' => 'manual',
            'name' => $name,
            'time' => $bell['time'],
            'season' => $bell['season'],
            'date' => now()->toDateString(),
            'suppressed' => false,
            'forced' => $force,
            'reason' => $force ? 'manual_forced' : null,
            'metadata' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'bells_to_ring' => [$bell],
                'should_ring' => true,
                'active_notifications' => $activeNotifications,
                'has_notifications' => $activeNotifications->count() > 0,
                'special_schedule_active' => (bool) $todaySpecial
            ]
        ]);
    }

    /**
     * Get recent bell logs with optional filters
     */
    public function recentBellLogs(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 50);
        $limit = max(1, min($limit, 200));

        $query = BellLog::query()->orderBy('date', 'desc')->orderBy('time', 'desc');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->get('date'));
        }
        if ($request->filled('season')) {
            $query->where('season', $request->get('season'));
        }
        if ($request->filled('schedule_type')) {
            $query->where('schedule_type', $request->get('schedule_type'));
        }
        if ($request->filled('suppressed')) {
            $query->where('suppressed', (bool) $request->boolean('suppressed'));
        }
        if ($request->filled('forced')) {
            $query->where('forced', (bool) $request->boolean('forced'));
        }

        $logs = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
            'count' => $logs->count(),
        ]);
    }
}