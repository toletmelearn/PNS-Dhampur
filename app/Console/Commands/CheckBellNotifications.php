<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BellTiming;
use App\Models\SpecialSchedule;
use App\Models\Holiday;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\BellLog;

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
        // Respect holidays unless a special schedule is active
        $isHolidayToday = Holiday::current()->exists();
        $todaySpecial = SpecialSchedule::getTodaySpecialSchedule();

        if ($isHolidayToday && !$todaySpecial) {
            // Persist suppressed log
            BellLog::create([
                'bell_timing_id' => null,
                'special_schedule_id' => null,
                'schedule_type' => 'regular',
                'ring_type' => 'auto',
                'name' => 'Holiday - No Bells',
                'time' => Carbon::now()->format('H:i'),
                'season' => BellTiming::getCurrentSeason(),
                'date' => now()->toDateString(),
                'suppressed' => true,
                'forced' => false,
                'reason' => 'holiday',
                'metadata' => null,
            ]);

            $this->info('Skipping bell notifications due to holiday.');
            Log::info('Bell notifications skipped: Holiday active and no special schedule.');
            return Command::SUCCESS;
        }

        $bellsToRing = collect();
        $currentTimeStr = Carbon::now()->format('H:i');

        if ($todaySpecial && is_array($todaySpecial->custom_timings) && count($todaySpecial->custom_timings) > 0) {
            foreach ($todaySpecial->custom_timings as $timing) {
                $timingStr = $timing['time'] ?? null;
                if ($timingStr && $timingStr === $currentTimeStr) {
                    // Create a transient BellTiming-like model for consistent handling
                    $bell = new BellTiming([
                        'name' => $timing['name'] ?? $todaySpecial->name,
                        'time' => Carbon::createFromFormat('H:i', $timingStr),
                        'season' => BellTiming::getCurrentSeason(),
                        'type' => $timing['type'] ?? 'start',
                        'description' => $todaySpecial->description ?? null,
                        'is_active' => true,
                    ]);
                    $bellsToRing->push($bell);
                }
            }
        } else {
            // Regular schedule
            $bellsToRing = BellTiming::checkBellTime();
        }

        if ($bellsToRing->count() > 0) {
            foreach ($bellsToRing as $bell) {
                $this->info("🔔 BELL NOTIFICATION: {$bell->name} at " . Carbon::parse($bell->time)->format('H:i'));
                
                // Log DB entry
                BellLog::create([
                    'bell_timing_id' => $bell->id,
                    'special_schedule_id' => $todaySpecial ? $todaySpecial->id : null,
                    'schedule_type' => $todaySpecial ? 'special' : 'regular',
                    'ring_type' => 'auto',
                    'name' => $bell->name,
                    'time' => Carbon::parse($bell->time)->format('H:i'),
                    'season' => $bell->season,
                    'date' => now()->toDateString(),
                    'suppressed' => false,
                    'forced' => false,
                    'reason' => null,
                    'metadata' => null,
                ]);
                
                // Log file entry
                Log::info('Bell notification triggered', [
                    'bell_name' => $bell->name,
                    'time' => Carbon::parse($bell->time)->format('H:i'),
                    'type' => $bell->type,
                    'season' => $bell->season,
                    'special_schedule' => (bool) $todaySpecial,
                ]);

                $this->triggerBellNotification($bell);
            }
        } else {
            $this->info('No bell notifications at this time.');
        }

        return Command::SUCCESS;
    }

    /**
     * Trigger bell notification (can be extended for different notification methods)
     */
    private function triggerBellNotification(BellTiming $bell)
    {
        $message = match($bell->type) {
            'start' => "🟢 {$bell->name} - Classes/Activities Starting",
            'end' => "🔴 {$bell->name} - Period/Activity Ending", 
            'break' => "🟡 {$bell->name} - Break Time",
            default => "🔔 {$bell->name}"
        };

        $this->line($message);
        
        // Persist lightweight notification for web interface
        $notificationFile = storage_path('app/bell_notifications.json');
        $notifications = [];
        
        if (file_exists($notificationFile)) {
            $notifications = json_decode(file_get_contents($notificationFile), true) ?? [];
        }
        
        $notifications[] = [
            'id' => uniqid(),
            'bell_id' => $bell->id,
            'name' => $bell->name,
            'time' => Carbon::parse($bell->time)->format('H:i'),
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