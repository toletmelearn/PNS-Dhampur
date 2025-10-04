<?php

namespace App\Http\Controllers;

use App\Models\BellNotification;
use App\Models\BellTiming;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class BellNotificationController extends Controller
{
    /**
     * Display a listing of bell notifications
     */
    public function index(Request $request): JsonResponse
    {
        $query = BellNotification::with('bellTiming');

        // Filter by bell timing
        if ($request->has('bell_timing_id')) {
            $query->where('bell_timing_id', $request->bell_timing_id);
        }

        // Filter by notification type
        if ($request->has('notification_type')) {
            $query->where('notification_type', $request->notification_type);
        }

        // Filter by enabled status
        if ($request->has('is_enabled')) {
            $query->where('is_enabled', $request->boolean('is_enabled'));
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        $notifications = $query->orderBy('priority', 'desc')
                              ->orderBy('notification_time')
                              ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'active_notifications' => BellNotification::getActiveNotifications(),
            'upcoming_notifications' => BellNotification::getUpcomingNotifications()
        ]);
    }

    /**
     * Store a newly created bell notification
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bell_timing_id' => 'required|exists:bell_timings,id',
            'notification_type' => ['required', Rule::in(['visual', 'audio', 'push', 'email', 'sms'])],
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'sound_file' => 'nullable|string|max:255',
            'is_enabled' => 'boolean',
            'notification_time' => 'nullable|integer|min:-300|max:300', // -5 to +5 minutes
            'target_audience' => ['required', Rule::in(['all', 'students', 'teachers', 'staff'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'auto_dismiss' => 'boolean',
            'dismiss_after_seconds' => 'nullable|integer|min:1|max:300'
        ]);

        $notification = BellNotification::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bell notification created successfully',
            'data' => $notification->load('bellTiming')
        ], 201);
    }

    /**
     * Display the specified bell notification
     */
    public function show(BellNotification $bellNotification): JsonResponse
    {
        $bellNotification->load('bellTiming');

        return response()->json([
            'success' => true,
            'data' => $bellNotification
        ]);
    }

    /**
     * Update the specified bell notification
     */
    public function update(Request $request, BellNotification $bellNotification): JsonResponse
    {
        $validated = $request->validate([
            'bell_timing_id' => 'sometimes|required|exists:bell_timings,id',
            'notification_type' => ['sometimes', 'required', Rule::in(['visual', 'audio', 'push', 'email', 'sms'])],
            'title' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string',
            'sound_file' => 'nullable|string|max:255',
            'is_enabled' => 'boolean',
            'notification_time' => 'nullable|integer|min:-300|max:300',
            'target_audience' => ['sometimes', 'required', Rule::in(['all', 'students', 'teachers', 'staff'])],
            'priority' => ['sometimes', 'required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'auto_dismiss' => 'boolean',
            'dismiss_after_seconds' => 'nullable|integer|min:1|max:300'
        ]);

        $bellNotification->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bell notification updated successfully',
            'data' => $bellNotification->load('bellTiming')
        ]);
    }

    /**
     * Remove the specified bell notification
     */
    public function destroy(BellNotification $bellNotification): JsonResponse
    {
        $bellNotification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bell notification deleted successfully'
        ]);
    }

    /**
     * Get active notifications
     */
    public function active(): JsonResponse
    {
        $activeNotifications = BellNotification::getActiveNotifications();

        return response()->json([
            'success' => true,
            'data' => $activeNotifications,
            'count' => $activeNotifications->count()
        ]);
    }

    /**
     * Get upcoming notifications
     */
    public function upcoming(): JsonResponse
    {
        $upcomingNotifications = BellNotification::getUpcomingNotifications();

        return response()->json([
            'success' => true,
            'data' => $upcomingNotifications,
            'count' => $upcomingNotifications->count()
        ]);
    }

    /**
     * Toggle notification enabled status
     */
    public function toggleEnabled(BellNotification $bellNotification): JsonResponse
    {
        $bellNotification->update(['is_enabled' => !$bellNotification->is_enabled]);

        return response()->json([
            'success' => true,
            'message' => 'Notification status updated successfully',
            'data' => $bellNotification
        ]);
    }

    /**
     * Test notification
     */
    public function testNotification(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:bell_notifications,id'
        ]);

        $notification = BellNotification::findOrFail($request->id);
        
        // Send test push notification
        $pushService = app(PushNotificationService::class);
        $result = $pushService->sendEmergencyNotification(
            $notification->title,
            $notification->message,
            [
                'type' => 'test',
                'sound' => $notification->sound_file,
                'notification_id' => $notification->id
            ]
        );
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Test notification sent successfully' : 'Failed to send test notification',
            'notification' => $notification,
            'push_result' => $result
        ]);
    }

    /**
     * Get pending push notifications
     */
    public function getPendingPushNotifications()
    {
        $pushService = app(PushNotificationService::class);
        $webNotifications = $pushService->getPendingWebNotifications();
        $bellNotifications = BellNotification::getActiveNotifications();
        
        return response()->json([
            'success' => true,
            'data' => [
                'web_notifications' => $webNotifications,
                'bell_notifications' => $bellNotifications,
                'total_count' => count($webNotifications) + $bellNotifications->count(),
                'timestamp' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Subscribe to push notifications
     */
    public function subscribeToPush(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string'
        ]);

        // Store subscription in session or database
        session(['push_subscription' => $request->all()]);

        return response()->json([
            'success' => true,
            'message' => 'Successfully subscribed to push notifications'
        ]);
    }

    /**
     * Get real-time schedule status
     */
    public function getScheduleStatus()
    {
        $currentTime = now();
        $currentSeason = BellTiming::getCurrentSeason();
        $nextBell = BellTiming::getNextBell();
        $currentSchedule = BellTiming::getCurrentSchedule();

        // Find current period
        $currentPeriod = null;
        $nextPeriod = null;

        foreach ($currentSchedule as $index => $bell) {
            $bellTime = \Carbon\Carbon::parse($bell->time);
            
            if ($currentTime->greaterThanOrEqualTo($bellTime)) {
                $currentPeriod = $bell;
                $nextPeriod = $currentSchedule->get($index + 1);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'current_time' => $currentTime->format('H:i:s'),
                'current_date' => $currentTime->format('Y-m-d'),
                'current_season' => $currentSeason,
                'current_period' => $currentPeriod,
                'next_period' => $nextPeriod,
                'next_bell' => $nextBell,
                'minutes_to_next_bell' => $nextBell ? $currentTime->diffInMinutes($nextBell->time) : null,
                'schedule' => $currentSchedule,
                'is_school_hours' => $this->isSchoolHours($currentTime),
                'period_progress' => $this->calculatePeriodProgress($currentPeriod, $nextPeriod, $currentTime)
            ]
        ]);
    }

    /**
     * Check if current time is within school hours
     */
    private function isSchoolHours($time): bool
    {
        $hour = $time->hour;
        $minute = $time->minute;
        $timeInMinutes = ($hour * 60) + $minute;

        // School hours: 7:00 AM to 6:00 PM
        $schoolStart = 7 * 60; // 7:00 AM in minutes
        $schoolEnd = 18 * 60;  // 6:00 PM in minutes

        return $timeInMinutes >= $schoolStart && $timeInMinutes <= $schoolEnd;
    }

    /**
     * Calculate period progress percentage
     */
    private function calculatePeriodProgress($currentPeriod, $nextPeriod, $currentTime): ?int
    {
        if (!$currentPeriod || !$nextPeriod) {
            return null;
        }

        $currentPeriodTime = \Carbon\Carbon::parse($currentPeriod->time);
        $nextPeriodTime = \Carbon\Carbon::parse($nextPeriod->time);

        $totalMinutes = $currentPeriodTime->diffInMinutes($nextPeriodTime);
        $elapsedMinutes = $currentPeriodTime->diffInMinutes($currentTime);

        if ($totalMinutes <= 0) {
            return 100;
        }

        $progress = ($elapsedMinutes / $totalMinutes) * 100;
        return min(100, max(0, (int) $progress));
    }

    /**
     * Bulk update notifications
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notifications' => 'required|array',
            'notifications.*.id' => 'required|exists:bell_notifications,id',
            'action' => ['required', Rule::in(['enable', 'disable', 'delete'])],
        ]);

        $notificationIds = collect($validated['notifications'])->pluck('id');
        $action = $validated['action'];

        switch ($action) {
            case 'enable':
                BellNotification::whereIn('id', $notificationIds)->update(['is_enabled' => true]);
                $message = 'Notifications enabled successfully';
                break;
            case 'disable':
                BellNotification::whereIn('id', $notificationIds)->update(['is_enabled' => false]);
                $message = 'Notifications disabled successfully';
                break;
            case 'delete':
                BellNotification::whereIn('id', $notificationIds)->delete();
                $message = 'Notifications deleted successfully';
                break;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'affected_count' => $notificationIds->count()
        ]);
    }

    /**
     * Create default notifications for a bell timing
     */
    public function createDefaults(BellTiming $bellTiming): JsonResponse
    {
        BellNotification::createDefaultNotifications($bellTiming->id);

        return response()->json([
            'success' => true,
            'message' => 'Default notifications created successfully',
            'data' => $bellTiming->notifications
        ]);
    }

    /**
     * Get notification statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_notifications' => BellNotification::count(),
            'enabled_notifications' => BellNotification::where('is_enabled', true)->count(),
            'notification_types' => BellNotification::selectRaw('notification_type, COUNT(*) as count')
                                                   ->groupBy('notification_type')
                                                   ->pluck('count', 'notification_type'),
            'priority_distribution' => BellNotification::selectRaw('priority, COUNT(*) as count')
                                                      ->groupBy('priority')
                                                      ->pluck('count', 'priority'),
            'target_audience_distribution' => BellNotification::selectRaw('target_audience, COUNT(*) as count')
                                                             ->groupBy('target_audience')
                                                             ->pluck('count', 'target_audience')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}