<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Assignment;
use App\Models\Syllabus;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Create assignment deadline notification
     */
    public function createAssignmentDeadlineNotification(Assignment $assignment, $daysBefore = 1)
    {
        try {
            $students = $assignment->getStudentsForClass();
            $deadlineDate = Carbon::parse($assignment->due_date);
            $notificationDate = $deadlineDate->subDays($daysBefore);

            $title = "Assignment Deadline Reminder";
            $message = "Assignment '{$assignment->title}' is due on {$deadlineDate->format('M d, Y')}. Don't forget to submit!";
            
            $data = [
                'assignment_id' => $assignment->id,
                'assignment_title' => $assignment->title,
                'due_date' => $assignment->due_date,
                'days_remaining' => $daysBefore
            ];

            $userIds = $students->pluck('id')->toArray();

            return Notification::createBulk(
                $userIds,
                'assignment_deadline',
                $title,
                $message,
                $data,
                [
                    'priority' => $daysBefore <= 1 ? 'high' : 'normal',
                    'scheduled_at' => $notificationDate,
                    'expires_at' => $deadlineDate->addDays(1)
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to create assignment deadline notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create assignment created notification
     */
    public function createAssignmentCreatedNotification(Assignment $assignment)
    {
        try {
            $students = $assignment->getStudentsForClass();
            
            $title = "New Assignment Available";
            $message = "A new assignment '{$assignment->title}' has been posted for {$assignment->subject->name}.";
            
            $data = [
                'assignment_id' => $assignment->id,
                'assignment_title' => $assignment->title,
                'subject' => $assignment->subject->name ?? 'Unknown',
                'due_date' => $assignment->due_date
            ];

            $userIds = $students->pluck('id')->toArray();

            return Notification::createBulk(
                $userIds,
                'assignment_created',
                $title,
                $message,
                $data,
                [
                    'priority' => 'normal',
                    'sender_id' => $assignment->teacher_id,
                    'sender_type' => 'teacher'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to create assignment created notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create assignment graded notification
     */
    public function createAssignmentGradedNotification($submission)
    {
        try {
            $title = "Assignment Graded";
            $message = "Your assignment '{$submission->assignment->title}' has been graded. Grade: {$submission->grade}";
            
            $data = [
                'assignment_id' => $submission->assignment_id,
                'submission_id' => $submission->id,
                'assignment_title' => $submission->assignment->title,
                'grade' => $submission->grade,
                'feedback' => $submission->feedback
            ];

            return Notification::createForUser(
                $submission->student_id,
                'assignment_graded',
                $title,
                $message,
                $data,
                [
                    'priority' => 'normal',
                    'sender_id' => $submission->assignment->teacher_id,
                    'sender_type' => 'teacher'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to create assignment graded notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create syllabus uploaded notification
     */
    public function createSyllabusUploadedNotification(Syllabus $syllabus)
    {
        try {
            $students = $this->getStudentsForSyllabus($syllabus);
            
            $title = "New Syllabus Available";
            $message = "A new syllabus for {$syllabus->subject->name} - {$syllabus->class->name} has been uploaded.";
            
            $data = [
                'syllabus_id' => $syllabus->id,
                'syllabus_title' => $syllabus->title,
                'subject' => $syllabus->subject->name ?? 'Unknown',
                'class' => $syllabus->class->name ?? 'Unknown'
            ];

            $userIds = $students->pluck('id')->toArray();

            return Notification::createBulk(
                $userIds,
                'syllabus_uploaded',
                $title,
                $message,
                $data,
                [
                    'priority' => 'normal',
                    'sender_id' => $syllabus->teacher_id,
                    'sender_type' => 'teacher'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to create syllabus uploaded notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create system announcement notification
     */
    public function createSystemAnnouncement($title, $message, $userIds = null, $priority = 'normal')
    {
        try {
            if ($userIds === null) {
                // Send to all active users
                $userIds = User::where('status', 'active')->pluck('id')->toArray();
            }

            $data = [
                'announcement_type' => 'system',
                'created_by' => 'system'
            ];

            return Notification::createBulk(
                $userIds,
                'system_announcement',
                $title,
                $message,
                $data,
                [
                    'priority' => $priority,
                    'sender_type' => 'system'
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to create system announcement: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send overdue assignment notifications
     */
    public function sendOverdueAssignmentNotifications()
    {
        try {
            $overdueAssignments = Assignment::where('due_date', '<', now())
                ->where('status', 'published')
                ->with(['submissions', 'subject', 'class'])
                ->get();

            foreach ($overdueAssignments as $assignment) {
                $submittedStudentIds = $assignment->submissions->pluck('student_id')->toArray();
                $allStudents = $assignment->getStudentsForClass();
                $nonSubmittedStudents = $allStudents->whereNotIn('id', $submittedStudentIds);

                if ($nonSubmittedStudents->count() > 0) {
                    $title = "Overdue Assignment";
                    $message = "Assignment '{$assignment->title}' was due on {$assignment->due_date->format('M d, Y')}. Please submit as soon as possible.";
                    
                    $data = [
                        'assignment_id' => $assignment->id,
                        'assignment_title' => $assignment->title,
                        'due_date' => $assignment->due_date,
                        'days_overdue' => now()->diffInDays($assignment->due_date)
                    ];

                    Notification::createBulk(
                        $nonSubmittedStudents->pluck('id')->toArray(),
                        'assignment_overdue',
                        $title,
                        $message,
                        $data,
                        [
                            'priority' => 'high',
                            'sender_id' => $assignment->teacher_id,
                            'sender_type' => 'teacher'
                        ]
                    );
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send overdue assignment notifications: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process scheduled notifications
     */
    public function processScheduledNotifications()
    {
        try {
            $scheduledNotifications = Notification::scheduled()
                ->where('scheduled_at', '<=', now())
                ->get();

            foreach ($scheduledNotifications as $notification) {
                // Mark as processed by removing scheduled_at
                $notification->update(['scheduled_at' => null]);
            }

            return $scheduledNotifications->count();
        } catch (\Exception $e) {
            Log::error('Failed to process scheduled notifications: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up expired notifications
     */
    public function cleanupExpiredNotifications()
    {
        try {
            $deletedCount = Notification::where('expires_at', '<', now())->delete();
            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired notifications: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get notification statistics for dashboard
     */
    public function getNotificationStats($userId = null)
    {
        try {
            $query = $userId ? Notification::forUser($userId) : Notification::query();

            return [
                'total_sent' => $query->count(),
                'total_read' => $query->where('is_read', true)->count(),
                'total_unread' => $query->where('is_read', false)->count(),
                'by_type' => $query->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->pluck('count', 'type'),
                'by_priority' => $query->selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
                'recent_activity' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get notification stats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Helper method to get students for a syllabus
     */
    private function getStudentsForSyllabus(Syllabus $syllabus)
    {
        // This would depend on your class-student relationship structure
        // Assuming you have a way to get students by class
        return User::where('role', 'student')
            ->where('class_id', $syllabus->class_id)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Create custom notification
     */
    public function createCustomNotification($userIds, $type, $title, $message, $data = null, $options = [])
    {
        try {
            if (is_array($userIds) && count($userIds) > 1) {
                return Notification::createBulk($userIds, $type, $title, $message, $data, $options);
            } else {
                $userId = is_array($userIds) ? $userIds[0] : $userIds;
                return Notification::createForUser($userId, $type, $title, $message, $data, $options);
            }
        } catch (\Exception $e) {
            Log::error('Failed to create custom notification: ' . $e->getMessage());
            return false;
        }
    }
}