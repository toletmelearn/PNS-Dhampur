<?php

namespace App\Http\Controllers;

use App\Models\BellTiming;
use App\Models\BellNotification;
use App\Models\SpecialSchedule;
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
        $bellsToRing = BellTiming::checkBellTime();
        $activeNotifications = BellNotification::getActiveNotifications();

        return response()->json([
            'success' => true,
            'data' => [
                'bells_to_ring' => $bellsToRing,
                'should_ring' => $bellsToRing->count() > 0,
                'active_notifications' => $activeNotifications,
                'has_notifications' => $activeNotifications->count() > 0
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
}