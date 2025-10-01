<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BellTiming;
use Illuminate\Support\Facades\Log;

class CheckBellNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bell:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for bell notifications and trigger alarms';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bellsToRing = BellTiming::checkBellTime();

        if ($bellsToRing->count() > 0) {
            foreach ($bellsToRing as $bell) {
                $this->info("🔔 BELL NOTIFICATION: {$bell->name} at {$bell->time->format('H:i')}");
                
                // Log the bell notification
                Log::info("Bell notification triggered", [
                    'bell_name' => $bell->name,
                    'time' => $bell->time->format('H:i'),
                    'type' => $bell->type,
                    'season' => $bell->season
                ]);

                // Here you can add additional notification logic:
                // - Send push notifications to mobile app
                // - Trigger physical bell system
                // - Send notifications to admin dashboard
                // - Play sound files
                
                $this->triggerBellNotification($bell);
            }
        } else {
            $this->info("No bell notifications at this time.");
        }

        return Command::SUCCESS;
    }

    /**
     * Trigger bell notification (can be extended for different notification methods)
     */
    private function triggerBellNotification(BellTiming $bell)
    {
        // This method can be extended to:
        // 1. Send notifications to connected devices
        // 2. Update a notification queue/cache
        // 3. Trigger external bell systems
        // 4. Send real-time notifications via WebSockets
        
        // For now, we'll just log and display
        $message = match($bell->type) {
            'start' => "🟢 {$bell->name} - Classes/Activities Starting",
            'end' => "🔴 {$bell->name} - Period/Activity Ending", 
            'break' => "🟡 {$bell->name} - Break Time",
            default => "🔔 {$bell->name}"
        };

        $this->line($message);
        
        // You can add file-based notification for the web interface
        $notificationFile = storage_path('app/bell_notifications.json');
        $notifications = [];
        
        if (file_exists($notificationFile)) {
            $notifications = json_decode(file_get_contents($notificationFile), true) ?? [];
        }
        
        $notifications[] = [
            'id' => uniqid(),
            'bell_id' => $bell->id,
            'name' => $bell->name,
            'time' => $bell->time->format('H:i'),
            'type' => $bell->type,
            'season' => $bell->season,
            'message' => $message,
            'triggered_at' => now()->toISOString(),
            'read' => false
        ];
        
        // Keep only last 50 notifications
        $notifications = array_slice($notifications, -50);
        
        file_put_contents($notificationFile, json_encode($notifications, JSON_PRETTY_PRINT));
    }
}