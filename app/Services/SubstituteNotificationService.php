<?php

namespace App\Services;

use App\Models\TeacherSubstitution;
use App\Models\Teacher;
use App\Models\Notification;
use App\Services\PushNotificationService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubstituteNotificationService
{
    protected $pushNotificationService;
    protected $smsService;

    public function __construct(PushNotificationService $pushNotificationService, SmsService $smsService)
    {
        $this->pushNotificationService = $pushNotificationService;
        $this->smsService = $smsService;
    }

    /**
     * Send assignment notification to substitute teacher
     */
    public function sendAssignmentNotification(TeacherSubstitution $substitution, Teacher $teacher): bool
    {
        try {
            // Create database notification
            $notification = Notification::create([
                'user_id' => $teacher->user_id,
                'title' => 'New Substitution Assignment',
                'message' => $this->getAssignmentMessage($substitution),
                'type' => 'substitution_assignment',
                'data' => json_encode([
                    'substitution_id' => $substitution->id,
                    'class' => $substitution->class->name ?? 'N/A',
                    'subject' => $substitution->subject ?? 'N/A',
                    'date' => $substitution->substitution_date,
                    'start_time' => $substitution->start_time,
                    'end_time' => $substitution->end_time,
                    'is_emergency' => $substitution->is_emergency
                ]),
                'priority' => $substitution->is_emergency ? 'high' : 'medium',
                'scheduled_for' => now()
            ]);

            // Send push notification
            $this->pushNotificationService->sendSubstitutionAssignment($teacher, $substitution);

            // Send SMS if mobile number available
            if ($teacher->mobile_number) {
                $this->sendSMSNotification($teacher->mobile_number, $this->getAssignmentMessage($substitution));
            }

            // Mark substitution as notification sent
            $substitution->update(['notification_sent' => true]);

            Log::info('Substitute assignment notification sent successfully', [
                'substitution_id' => $substitution->id,
                'teacher_id' => $teacher->id,
                'notification_id' => $notification->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send substitute assignment notification', [
                'substitution_id' => $substitution->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send confirmation notification to original teacher
     */
    public function sendConfirmationNotification(TeacherSubstitution $substitution): bool
    {
        try {
            if (!$substitution->originalTeacher || !$substitution->substituteTeacher) {
                return false;
            }

            // Create database notification for original teacher
            Notification::create([
                'user_id' => $substitution->originalTeacher->user_id,
                'title' => 'Substitute Teacher Confirmed',
                'message' => $this->getConfirmationMessage($substitution),
                'type' => 'substitute_confirmed',
                'data' => json_encode([
                    'substitution_id' => $substitution->id,
                    'substitute_teacher' => $substitution->substituteTeacher->user->name,
                    'class' => $substitution->class->name ?? 'N/A',
                    'date' => $substitution->substitution_date
                ]),
                'priority' => 'medium'
            ]);

            // Send push notification
            $this->pushNotificationService->sendSubstituteConfirmation(
                $substitution->originalTeacher,
                $substitution->substituteTeacher,
                $substitution
            );

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send substitute confirmation notification', [
                'substitution_id' => $substitution->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send reminder notification before class starts
     */
    public function sendReminderNotification(TeacherSubstitution $substitution, int $minutesBefore = 15): bool
    {
        try {
            if (!$substitution->substituteTeacher) {
                return false;
            }

            $classTime = Carbon::parse($substitution->substitution_date . ' ' . $substitution->start_time);
            $reminderTime = $classTime->subMinutes($minutesBefore);

            // Only send if reminder time is in the future
            if ($reminderTime->isPast()) {
                return false;
            }

            // Create scheduled notification
            Notification::create([
                'user_id' => $substitution->substituteTeacher->user_id,
                'title' => 'Upcoming Substitution Reminder',
                'message' => $this->getReminderMessage($substitution, $minutesBefore),
                'type' => 'substitution_reminder',
                'data' => json_encode([
                    'substitution_id' => $substitution->id,
                    'class' => $substitution->class->name ?? 'N/A',
                    'subject' => $substitution->subject ?? 'N/A',
                    'start_time' => $substitution->start_time,
                    'minutes_before' => $minutesBefore
                ]),
                'priority' => 'medium',
                'scheduled_for' => $reminderTime
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to schedule substitute reminder notification', [
                'substitution_id' => $substitution->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send emergency cancellation notification
     */
    public function sendCancellationNotification(TeacherSubstitution $substitution, string $reason): bool
    {
        try {
            // Notify substitute teacher if assigned
            if ($substitution->substituteTeacher) {
                Notification::create([
                    'user_id' => $substitution->substituteTeacher->user_id,
                    'title' => 'Substitution Cancelled',
                    'message' => "Your substitution assignment for {$substitution->class->name} on {$substitution->substitution_date} has been cancelled. Reason: {$reason}",
                    'type' => 'substitution_cancelled',
                    'data' => json_encode([
                        'substitution_id' => $substitution->id,
                        'reason' => $reason
                    ]),
                    'priority' => 'high'
                ]);
            }

            // Send emergency cancellation push notification
            $this->pushNotificationService->sendEmergencyCancellation(
                $substitution->class->name ?? 'Unknown Class',
                $substitution->subject ?? 'Unknown Subject',
                $substitution->substitution_date,
                $reason
            );

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send cancellation notification', [
                'substitution_id' => $substitution->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Process scheduled notifications
     */
    public function processScheduledNotifications(): int
    {
        $processedCount = 0;

        try {
            // Get notifications scheduled for now or past
            $scheduledNotifications = Notification::where('scheduled_for', '<=', now())
                ->whereNull('sent_at')
                ->where('type', 'LIKE', 'substitution_%')
                ->get();

            foreach ($scheduledNotifications as $notification) {
                $this->sendScheduledNotification($notification);
                $processedCount++;
            }

            Log::info('Processed scheduled substitute notifications', [
                'count' => $processedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process scheduled notifications', [
                'error' => $e->getMessage()
            ]);
        }

        return $processedCount;
    }

    /**
     * Send SMS notification (placeholder for SMS service integration)
     */
    private function sendSMSNotification(string $phoneNumber, string $message): bool
    {
        try {
            $sent = $this->smsService->send($phoneNumber, $message);
            if (!$sent) {
                Log::warning('Failed to send SMS via provider', [
                    'phone' => $phoneNumber,
                    'message' => $message,
                ]);
            }
            return $sent;
        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Send scheduled notification
     */
    private function sendScheduledNotification(Notification $notification): void
    {
        try {
            $data = json_decode($notification->data, true);

            if ($notification->type === 'substitution_reminder') {
                // Send push notification for reminder
                $substitution = TeacherSubstitution::find($data['substitution_id']);
                if ($substitution && $substitution->substituteTeacher) {
                    $this->pushNotificationService->sendSubstitutionAssignment(
                        $substitution->substituteTeacher,
                        $substitution
                    );
                }
            }

            // Mark as sent
            $notification->update(['sent_at' => now()]);

        } catch (\Exception $e) {
            Log::error('Failed to send scheduled notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get assignment message
     */
    private function getAssignmentMessage(TeacherSubstitution $substitution): string
    {
        $class = $substitution->class->name ?? 'Unknown Class';
        $subject = $substitution->subject ?? 'Unknown Subject';
        $date = Carbon::parse($substitution->substitution_date)->format('M j, Y');
        $time = "{$substitution->start_time} - {$substitution->end_time}";
        
        $urgency = $substitution->is_emergency ? ' (URGENT)' : '';
        
        return "You have been assigned to substitute {$class} - {$subject} on {$date} from {$time}{$urgency}. Please confirm your availability.";
    }

    /**
     * Get confirmation message
     */
    private function getConfirmationMessage(TeacherSubstitution $substitution): string
    {
        $substituteName = $substitution->substituteTeacher->user->name ?? 'Unknown Teacher';
        $class = $substitution->class->name ?? 'Unknown Class';
        $date = Carbon::parse($substitution->substitution_date)->format('M j, Y');
        
        return "{$substituteName} has been confirmed as your substitute for {$class} on {$date}.";
    }

    /**
     * Get reminder message
     */
    private function getReminderMessage(TeacherSubstitution $substitution, int $minutesBefore): string
    {
        $class = $substitution->class->name ?? 'Unknown Class';
        $subject = $substitution->subject ?? 'Unknown Subject';
        
        return "Reminder: Your substitution for {$class} - {$subject} starts in {$minutesBefore} minutes.";
    }
}