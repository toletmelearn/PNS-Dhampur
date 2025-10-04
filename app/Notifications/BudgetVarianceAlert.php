<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\Budget;
use App\Models\Transaction;

class BudgetVarianceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $budget;
    protected $variance;
    protected $alertType;
    protected $department;
    protected $currentSpent;
    protected $budgetAmount;

    /**
     * Create a new notification instance.
     */
    public function __construct(Budget $budget, $variance, $alertType = 'warning', $department = null)
    {
        $this->budget = $budget;
        $this->variance = $variance;
        $this->alertType = $alertType; // 'warning', 'critical', 'exceeded'
        $this->department = $department;
        $this->currentSpent = $budget->spent_amount;
        $this->budgetAmount = $budget->total_budget;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = ['database'];
        
        // Add email for critical alerts
        if (in_array($this->alertType, ['critical', 'exceeded'])) {
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
            ->line($this->getBudgetDetails())
            ->action('View Budget Dashboard', url('/budget-tracking'))
            ->line('Please take immediate action to control expenses.')
            ->salutation('Best regards, PNS Dhampur System');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => 'budget_variance_alert',
            'alert_type' => $this->alertType,
            'budget_id' => $this->budget->id,
            'budget_year' => $this->budget->year,
            'department' => $this->department,
            'variance_percentage' => $this->variance,
            'current_spent' => $this->currentSpent,
            'budget_amount' => $this->budgetAmount,
            'remaining_budget' => $this->budgetAmount - $this->currentSpent,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'priority' => $this->getPriority(),
            'action_url' => '/budget-tracking',
            'created_at' => now(),
        ];
    }

    /**
     * Get notification title based on alert type
     */
    private function getNotificationTitle()
    {
        switch ($this->alertType) {
            case 'warning':
                return 'Budget Warning Alert';
            case 'critical':
                return 'Critical Budget Alert';
            case 'exceeded':
                return 'Budget Exceeded Alert';
            default:
                return 'Budget Variance Alert';
        }
    }

    /**
     * Get notification message
     */
    private function getNotificationMessage()
    {
        $departmentText = $this->department ? " for {$this->department} department" : '';
        
        switch ($this->alertType) {
            case 'warning':
                return "Budget{$departmentText} has reached {$this->variance}% of allocated amount. Current spending: ₹" . number_format($this->currentSpent, 2);
            case 'critical':
                return "Critical: Budget{$departmentText} has reached {$this->variance}% of allocated amount. Immediate attention required!";
            case 'exceeded':
                return "Alert: Budget{$departmentText} has been exceeded by {$this->variance}%. Current overspend: ₹" . number_format($this->currentSpent - $this->budgetAmount, 2);
            default:
                return "Budget variance detected{$departmentText}. Current variance: {$this->variance}%";
        }
    }

    /**
     * Get email subject
     */
    private function getEmailSubject()
    {
        $departmentText = $this->department ? " - {$this->department}" : '';
        
        switch ($this->alertType) {
            case 'warning':
                return "Budget Warning Alert{$departmentText} - PNS Dhampur";
            case 'critical':
                return "🚨 Critical Budget Alert{$departmentText} - PNS Dhampur";
            case 'exceeded':
                return "⚠️ Budget Exceeded Alert{$departmentText} - PNS Dhampur";
            default:
                return "Budget Variance Alert{$departmentText} - PNS Dhampur";
        }
    }

    /**
     * Get email greeting
     */
    private function getEmailGreeting()
    {
        switch ($this->alertType) {
            case 'critical':
            case 'exceeded':
                return 'Urgent Budget Alert!';
            default:
                return 'Budget Notification';
        }
    }

    /**
     * Get email message
     */
    private function getEmailMessage()
    {
        $departmentText = $this->department ? " for the {$this->department} department" : '';
        
        switch ($this->alertType) {
            case 'warning':
                return "The budget{$departmentText} has reached {$this->variance}% of the allocated amount. Please monitor expenses closely to avoid overspending.";
            case 'critical':
                return "The budget{$departmentText} has reached a critical level of {$this->variance}% of the allocated amount. Immediate action is required to control expenses.";
            case 'exceeded':
                return "The budget{$departmentText} has been exceeded by {$this->variance}%. Please review and take corrective measures immediately.";
            default:
                return "A budget variance has been detected{$departmentText}. Please review the current spending status.";
        }
    }

    /**
     * Get budget details for email
     */
    private function getBudgetDetails()
    {
        $remaining = $this->budgetAmount - $this->currentSpent;
        $utilizationPercentage = ($this->currentSpent / $this->budgetAmount) * 100;
        
        return sprintf(
            "Budget Details:\n• Total Budget: ₹%s\n• Current Spent: ₹%s\n• Remaining: ₹%s\n• Utilization: %.1f%%",
            number_format($this->budgetAmount, 2),
            number_format($this->currentSpent, 2),
            number_format($remaining, 2),
            $utilizationPercentage
        );
    }

    /**
     * Get notification priority
     */
    private function getPriority()
    {
        switch ($this->alertType) {
            case 'exceeded':
                return 'high';
            case 'critical':
                return 'high';
            case 'warning':
                return 'medium';
            default:
                return 'low';
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