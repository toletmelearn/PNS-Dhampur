<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\MaintenanceSchedule;
use Carbon\Carbon;

class MaintenanceReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $maintenanceSchedule;
    protected $reminderType;
    protected $daysUntilDue;

    /**
     * Create a new notification instance.
     */
    public function __construct(MaintenanceSchedule $maintenanceSchedule, $reminderType = 'upcoming')
    {
        $this->maintenanceSchedule = $maintenanceSchedule;
        $this->reminderType = $reminderType; // 'upcoming', 'due_today', 'overdue', 'completed', 'cancelled'
        $this->daysUntilDue = $this->calculateDaysUntilDue();
    }

    /**
     * Calculate days until due
     */
    private function calculateDaysUntilDue()
    {
        $scheduledDate = Carbon::parse($this->maintenanceSchedule->scheduled_date);
        $today = Carbon::today();
        
        return $today->diffInDays($scheduledDate, false);
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = ['database'];
        
        // Add email for overdue and due today alerts
        if (in_array($this->reminderType, ['overdue', 'due_today', 'completed'])) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $subject = $this->getEmailSubject();
        $greeting = $this->getEmailGreeting();
        $message = $this->getEmailMessage();
        
        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($message)
            ->line($this->getMaintenanceDetails())
            ->action('View Maintenance Schedule', url('/inventory-management/maintenance/' . $this->maintenanceSchedule->id))
            ->line($this->getActionMessage())
            ->salutation('Best regards, PNS Dhampur Maintenance System');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'maintenance_reminder',
            'reminder_type' => $this->reminderType,
            'maintenance_schedule_id' => $this->maintenanceSchedule->id,
            'inventory_item_id' => $this->maintenanceSchedule->inventory_item_id,
            'item_name' => $this->maintenanceSchedule->inventoryItem->name,
            'item_code' => $this->maintenanceSchedule->inventoryItem->item_code,
            'maintenance_type' => $this->maintenanceSchedule->maintenance_type,
            'scheduled_date' => $this->maintenanceSchedule->scheduled_date,
            'estimated_cost' => $this->maintenanceSchedule->estimated_cost,
            'priority' => $this->maintenanceSchedule->priority,
            'status' => $this->maintenanceSchedule->status,
            'assigned_to' => $this->maintenanceSchedule->assigned_to,
            'days_until_due' => $this->daysUntilDue,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'notification_priority' => $this->getNotificationPriority(),
            'action_url' => '/inventory-management/maintenance/' . $this->maintenanceSchedule->id,
            'suggested_action' => $this->getSuggestedAction(),
            'created_at' => now(),
        ];
    }

    /**
     * Get notification title based on reminder type
     */
    private function getNotificationTitle()
    {
        switch ($this->reminderType) {
            case 'upcoming':
                return 'Upcoming Maintenance Reminder';
            case 'due_today':
                return 'Maintenance Due Today';
            case 'overdue':
                return 'Overdue Maintenance Alert';
            case 'completed':
                return 'Maintenance Completed';
            case 'cancelled':
                return 'Maintenance Cancelled';
            default:
                return 'Maintenance Notification';
        }
    }

    /**
     * Get notification message
     */
    private function getNotificationMessage()
    {
        $itemName = $this->maintenanceSchedule->inventoryItem->name;
        $maintenanceType = $this->maintenanceSchedule->maintenance_type;
        $scheduledDate = Carbon::parse($this->maintenanceSchedule->scheduled_date)->format('M d, Y');
        
        switch ($this->reminderType) {
            case 'upcoming':
                $daysText = abs($this->daysUntilDue) == 1 ? 'day' : 'days';
                return "{$maintenanceType} maintenance for '{$itemName}' is scheduled in {$this->daysUntilDue} {$daysText} ({$scheduledDate})";
            case 'due_today':
                return "{$maintenanceType} maintenance for '{$itemName}' is due today ({$scheduledDate})";
            case 'overdue':
                $overdueDays = abs($this->daysUntilDue);
                $daysText = $overdueDays == 1 ? 'day' : 'days';
                return "{$maintenanceType} maintenance for '{$itemName}' is overdue by {$overdueDays} {$daysText} (was due: {$scheduledDate})";
            case 'completed':
                return "{$maintenanceType} maintenance for '{$itemName}' has been completed successfully";
            case 'cancelled':
                return "{$maintenanceType} maintenance for '{$itemName}' scheduled for {$scheduledDate} has been cancelled";
            default:
                return "Maintenance notification for '{$itemName}'";
        }
    }

    /**
     * Get email subject
     */
    private function getEmailSubject()
    {
        $itemName = $this->maintenanceSchedule->inventoryItem->name;
        
        switch ($this->reminderType) {
            case 'upcoming':
                return "📅 Upcoming Maintenance: {$itemName} - PNS Dhampur";
            case 'due_today':
                return "⏰ Maintenance Due Today: {$itemName} - PNS Dhampur";
            case 'overdue':
                return "🚨 Overdue Maintenance Alert: {$itemName} - PNS Dhampur";
            case 'completed':
                return "✅ Maintenance Completed: {$itemName} - PNS Dhampur";
            case 'cancelled':
                return "❌ Maintenance Cancelled: {$itemName} - PNS Dhampur";
            default:
                return "Maintenance Notification: {$itemName} - PNS Dhampur";
        }
    }

    /**
     * Get email greeting
     */
    private function getEmailGreeting()
    {
        switch ($this->reminderType) {
            case 'overdue':
                return 'Urgent Maintenance Alert!';
            case 'due_today':
                return 'Maintenance Due Today!';
            case 'completed':
                return 'Maintenance Update';
            default:
                return 'Maintenance Reminder';
        }
    }

    /**
     * Get email message
     */
    private function getEmailMessage()
    {
        $itemName = $this->maintenanceSchedule->inventoryItem->name;
        $itemCode = $this->maintenanceSchedule->inventoryItem->item_code;
        $maintenanceType = $this->maintenanceSchedule->maintenance_type;
        $scheduledDate = Carbon::parse($this->maintenanceSchedule->scheduled_date)->format('F d, Y');
        
        switch ($this->reminderType) {
            case 'upcoming':
                $daysText = abs($this->daysUntilDue) == 1 ? 'day' : 'days';
                return "This is a reminder that {$maintenanceType} maintenance for '{$itemName}' (Code: {$itemCode}) is scheduled in {$this->daysUntilDue} {$daysText} on {$scheduledDate}. Please ensure all necessary preparations are made.";
            case 'due_today':
                return "The {$maintenanceType} maintenance for '{$itemName}' (Code: {$itemCode}) is due today ({$scheduledDate}). Please proceed with the scheduled maintenance as planned.";
            case 'overdue':
                $overdueDays = abs($this->daysUntilDue);
                $daysText = $overdueDays == 1 ? 'day' : 'days';
                return "The {$maintenanceType} maintenance for '{$itemName}' (Code: {$itemCode}) is now overdue by {$overdueDays} {$daysText}. It was scheduled for {$scheduledDate}. Please complete this maintenance as soon as possible to avoid potential issues.";
            case 'completed':
                return "The {$maintenanceType} maintenance for '{$itemName}' (Code: {$itemCode}) has been successfully completed. Thank you for maintaining our equipment properly.";
            case 'cancelled':
                return "The {$maintenanceType} maintenance for '{$itemName}' (Code: {$itemCode}) scheduled for {$scheduledDate} has been cancelled. Please review if rescheduling is necessary.";
            default:
                return "This is a maintenance notification for '{$itemName}' (Code: {$itemCode}). Please review the maintenance schedule.";
        }
    }

    /**
     * Get maintenance details for email
     */
    private function getMaintenanceDetails()
    {
        $assignedTo = $this->maintenanceSchedule->assignedTo->name ?? 'Not assigned';
        $estimatedCost = $this->maintenanceSchedule->estimated_cost ? '₹' . number_format($this->maintenanceSchedule->estimated_cost, 2) : 'Not specified';
        $priority = ucfirst($this->maintenanceSchedule->priority);
        $location = $this->maintenanceSchedule->inventoryItem->location ?? 'Not specified';
        
        return sprintf(
            "Maintenance Details:\n• Item Code: %s\n• Location: %s\n• Maintenance Type: %s\n• Priority: %s\n• Assigned To: %s\n• Estimated Cost: %s\n• Estimated Duration: %s hours\n• Status: %s",
            $this->maintenanceSchedule->inventoryItem->item_code,
            $location,
            $this->maintenanceSchedule->maintenance_type,
            $priority,
            $assignedTo,
            $estimatedCost,
            $this->maintenanceSchedule->estimated_duration ?? 'Not specified',
            ucfirst($this->maintenanceSchedule->status)
        );
    }

    /**
     * Get action message for email
     */
    private function getActionMessage()
    {
        switch ($this->reminderType) {
            case 'overdue':
                return 'Please complete this overdue maintenance immediately to prevent equipment failure.';
            case 'due_today':
                return 'Please proceed with the scheduled maintenance today.';
            case 'upcoming':
                return 'Please prepare for the upcoming maintenance and ensure all resources are available.';
            case 'completed':
                return 'No further action required. The maintenance has been completed successfully.';
            case 'cancelled':
                return 'Please review if this maintenance needs to be rescheduled.';
            default:
                return 'Please review the maintenance schedule and take appropriate action.';
        }
    }

    /**
     * Get notification priority
     */
    private function getNotificationPriority()
    {
        switch ($this->reminderType) {
            case 'overdue':
                return 'critical';
            case 'due_today':
                return 'high';
            case 'upcoming':
                return $this->maintenanceSchedule->priority === 'high' ? 'high' : 'medium';
            case 'completed':
                return 'low';
            case 'cancelled':
                return 'low';
            default:
                return 'medium';
        }
    }

    /**
     * Get suggested action
     */
    private function getSuggestedAction()
    {
        switch ($this->reminderType) {
            case 'overdue':
                return 'Complete maintenance immediately';
            case 'due_today':
                return 'Start scheduled maintenance';
            case 'upcoming':
                return 'Prepare for upcoming maintenance';
            case 'completed':
                return 'Review maintenance report';
            case 'cancelled':
                return 'Review if rescheduling is needed';
            default:
                return 'Review maintenance details';
        }
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}