<?php

namespace App\Services;

use App\Models\BellTiming;
use App\Models\BellNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PushNotificationService
{
    /**
     * Send push notification for bell timing
     */
    public function sendBellNotification(BellTiming $bellTiming, array $recipients = [])
    {
        try {
            $notification = [
                'title' => $this->getBellNotificationTitle($bellTiming),
                'body' => $this->getBellNotificationBody($bellTiming),
                'icon' => '/images/bell-icon.png',
                'badge' => '/images/bell-badge.png',
                'tag' => 'bell-' . $bellTiming->id,
                'requireInteraction' => false,
                'silent' => false,
                'timestamp' => now()->timestamp * 1000,
                'data' => [
                    'bell_id' => $bellTiming->id,
                    'bell_name' => $bellTiming->name,
                    'bell_time' => $bellTiming->time->format('H:i'),
                    'bell_type' => $bellTiming->type,
                    'season' => $bellTiming->season,
                    'url' => route('dashboard')
                ],
                'actions' => [
                    [
                        'action' => 'view',
                        'title' => 'View Schedule',
                        'icon' => '/images/schedule-icon.png'
                    ],
                    [
                        'action' => 'dismiss',
                        'title' => 'Dismiss',
                        'icon' => '/images/dismiss-icon.png'
                    ]
                ]
            ];

            // Store notification in session for web clients
            $this->storeWebNotification($notification);

            // Log the notification
            Log::info('Bell notification sent', [
                'bell_id' => $bellTiming->id,
                'bell_name' => $bellTiming->name,
                'recipients_count' => count($recipients)
            ]);

            return [
                'success' => true,
                'notification' => $notification,
                'recipients_count' => count($recipients)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send bell notification', [
                'bell_id' => $bellTiming->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send period change notification
     */
    public function sendPeriodChangeNotification($currentPeriod, $nextPeriod)
    {
        try {
            $notification = [
                'title' => 'Period Change Alert',
                'body' => $this->getPeriodChangeMessage($currentPeriod, $nextPeriod),
                'icon' => '/images/period-change-icon.png',
                'badge' => '/images/period-badge.png',
                'tag' => 'period-change-' . now()->timestamp,
                'requireInteraction' => true,
                'silent' => false,
                'timestamp' => now()->timestamp * 1000,
                'data' => [
                    'type' => 'period_change',
                    'current_period' => $currentPeriod,
                    'next_period' => $nextPeriod,
                    'time' => now()->format('H:i'),
                    'url' => route('dashboard')
                ],
                'actions' => [
                    [
                        'action' => 'acknowledge',
                        'title' => 'Got it',
                        'icon' => '/images/check-icon.png'
                    ]
                ]
            ];

            $this->storeWebNotification($notification);

            return [
                'success' => true,
                'notification' => $notification
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send period change notification', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send emergency notification
     */
    public function sendEmergencyNotification($title, $message, $data = [])
    {
        try {
            $notification = [
                'title' => '🚨 ' . $title,
                'body' => $message,
                'icon' => '/images/emergency-icon.png',
                'badge' => '/images/emergency-badge.png',
                'tag' => 'emergency-' . now()->timestamp,
                'requireInteraction' => true,
                'silent' => false,
                'vibrate' => [200, 100, 200, 100, 200],
                'timestamp' => now()->timestamp * 1000,
                'data' => array_merge([
                    'type' => 'emergency',
                    'priority' => 'urgent',
                    'time' => now()->format('H:i:s'),
                    'url' => route('dashboard')
                ], $data),
                'actions' => [
                    [
                        'action' => 'acknowledge',
                        'title' => 'Acknowledge',
                        'icon' => '/images/check-icon.png'
                    ]
                ]
            ];

            $this->storeWebNotification($notification);

            return [
                'success' => true,
                'notification' => $notification
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send emergency notification', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get bell notification title
     */
    private function getBellNotificationTitle(BellTiming $bellTiming)
    {
        return match($bellTiming->type) {
            'start' => '🟢 ' . $bellTiming->name,
            'end' => '🔴 ' . $bellTiming->name,
            'break' => '🟡 ' . $bellTiming->name,
            default => '🔔 ' . $bellTiming->name
        };
    }

    /**
     * Get bell notification body
     */
    private function getBellNotificationBody(BellTiming $bellTiming)
    {
        $time = $bellTiming->time->format('H:i');
        
        return match($bellTiming->type) {
            'start' => "Classes starting at {$time}",
            'end' => "Period ending at {$time}",
            'break' => "Break time at {$time}",
            default => "Bell ringing at {$time}"
        };
    }

    /**
     * Get period change message
     */
    private function getPeriodChangeMessage($currentPeriod, $nextPeriod)
    {
        if ($nextPeriod) {
            return "Current: {$currentPeriod} → Next: {$nextPeriod}";
        }
        
        return "Current period: {$currentPeriod} is ending";
    }

    /**
     * Store notification for web clients
     */
    private function storeWebNotification($notification)
    {
        $notificationFile = storage_path('app/web_notifications.json');
        $notifications = [];
        
        if (file_exists($notificationFile)) {
            $notifications = json_decode(file_get_contents($notificationFile), true) ?? [];
        }
        
        $notifications[] = array_merge($notification, [
            'id' => uniqid(),
            'created_at' => now()->toISOString(),
            'read' => false
        ]);
        
        // Keep only last 100 notifications
        $notifications = array_slice($notifications, -100);
        
        file_put_contents($notificationFile, json_encode($notifications, JSON_PRETTY_PRINT));
    }

    /**
     * Get pending web notifications
     */
    public function getPendingWebNotifications()
    {
        $notificationFile = storage_path('app/web_notifications.json');
        
        if (!file_exists($notificationFile)) {
            return [];
        }
        
        $notifications = json_decode(file_get_contents($notificationFile), true) ?? [];
        
        // Return unread notifications from the last 5 minutes
        $fiveMinutesAgo = now()->subMinutes(5);
        
        return array_filter($notifications, function($notification) use ($fiveMinutesAgo) {
            $createdAt = Carbon::parse($notification['created_at']);
            return !$notification['read'] && $createdAt->greaterThan($fiveMinutesAgo);
        });
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notificationId)
    {
        $notificationFile = storage_path('app/web_notifications.json');
        
        if (!file_exists($notificationFile)) {
            return false;
        }
        
        $notifications = json_decode(file_get_contents($notificationFile), true) ?? [];
        
        foreach ($notifications as &$notification) {
            if ($notification['id'] === $notificationId) {
                $notification['read'] = true;
                break;
            }
        }
        
        file_put_contents($notificationFile, json_encode($notifications, JSON_PRETTY_PRINT));
        
        return true;
    }

    /**
     * Clear old notifications
     */
    public function clearOldNotifications($olderThanHours = 24)
    {
        $notificationFile = storage_path('app/web_notifications.json');
        
        if (!file_exists($notificationFile)) {
            return 0;
        }
        
        $notifications = json_decode(file_get_contents($notificationFile), true) ?? [];
        $cutoffTime = now()->subHours($olderThanHours);
        
        $filteredNotifications = array_filter($notifications, function($notification) use ($cutoffTime) {
            $createdAt = Carbon::parse($notification['created_at']);
            return $createdAt->greaterThan($cutoffTime);
        });
        
        $removedCount = count($notifications) - count($filteredNotifications);
        
        file_put_contents($notificationFile, json_encode(array_values($filteredNotifications), JSON_PRETTY_PRINT));
        
        return $removedCount;
    }
}