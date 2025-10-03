<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\Assignment;
use App\Models\Notification;
use Carbon\Carbon;

class SendNotificationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-reminders {--type=all : Type of reminders to send (all, deadlines, scheduled)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification reminders for assignment deadlines and process scheduled notifications';

    protected $notificationService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->option('type');
        
        $this->info('Starting notification reminders process...');
        
        try {
            switch ($type) {
                case 'deadlines':
                    $this->sendDeadlineReminders();
                    break;
                case 'scheduled':
                    $this->processScheduledNotifications();
                    break;
                case 'all':
                default:
                    $this->sendDeadlineReminders();
                    $this->processScheduledNotifications();
                    $this->cleanupExpiredNotifications();
                    break;
            }
            
            $this->info('Notification reminders process completed successfully!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error processing notifications: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Send deadline reminders for assignments
     */
    protected function sendDeadlineReminders()
    {
        $this->info('Sending assignment deadline reminders...');
        
        // Get assignments with deadlines in the next 24 hours and 1 hour
        $tomorrow = Carbon::now()->addDay();
        $nextHour = Carbon::now()->addHour();
        
        // 24-hour reminders
        $assignmentsTomorrow = Assignment::where('due_date', '>=', Carbon::now())
            ->where('due_date', '<=', $tomorrow)
            ->where('status', 'published')
            ->get();
            
        foreach ($assignmentsTomorrow as $assignment) {
            $this->notificationService->sendOverdueAssignmentNotifications($assignment->id);
            $this->line("Sent 24-hour reminder for assignment: {$assignment->title}");
        }
        
        // 1-hour reminders
        $assignmentsNextHour = Assignment::where('due_date', '>=', Carbon::now())
            ->where('due_date', '<=', $nextHour)
            ->where('status', 'published')
            ->get();
            
        foreach ($assignmentsNextHour as $assignment) {
            $this->notificationService->sendOverdueAssignmentNotifications($assignment->id);
            $this->line("Sent 1-hour reminder for assignment: {$assignment->title}");
        }
        
        $this->info("Processed " . ($assignmentsTomorrow->count() + $assignmentsNextHour->count()) . " deadline reminders");
    }

    /**
     * Process scheduled notifications
     */
    protected function processScheduledNotifications()
    {
        $this->info('Processing scheduled notifications...');
        
        $scheduledNotifications = Notification::where('scheduled_at', '<=', Carbon::now())
            ->whereNull('sent_at')
            ->get();
            
        $processed = 0;
        
        foreach ($scheduledNotifications as $notification) {
            try {
                // Mark as sent
                $notification->update(['sent_at' => Carbon::now()]);
                
                // Here you could add logic to actually send the notification
                // via email, SMS, push notification, etc.
                
                $this->line("Processed scheduled notification: {$notification->title}");
                $processed++;
                
            } catch (\Exception $e) {
                $this->error("Failed to process notification {$notification->id}: " . $e->getMessage());
            }
        }
        
        $this->info("Processed {$processed} scheduled notifications");
    }

    /**
     * Clean up expired notifications
     */
    protected function cleanupExpiredNotifications()
    {
        $this->info('Cleaning up expired notifications...');
        
        $expiredCount = Notification::where('expires_at', '<', Carbon::now())
            ->delete();
            
        $this->info("Cleaned up {$expiredCount} expired notifications");
    }
}