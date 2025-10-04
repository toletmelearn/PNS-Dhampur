<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\VendorPerformance;

class VendorPerformanceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $performance;
    protected $alertType;
    protected $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct(VendorPerformance $performance, $alertType = 'poor_performance', $additionalData = [])
    {
        $this->performance = $performance;
        $this->alertType = $alertType;
        $this->additionalData = $additionalData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getSubject())
            ->greeting($this->getGreeting())
            ->line($this->getMessage())
            ->action('View Vendor Performance', $this->getActionUrl());

        if ($this->alertType === 'poor_performance') {
            $mailMessage->error();
        } elseif ($this->alertType === 'high_risk') {
            $mailMessage->error();
        } else {
            $mailMessage->success();
        }

        // Add performance details
        $mailMessage->line('Performance Details:')
            ->line('• Performance Score: ' . $this->performance->performance_score . '/5.0')
            ->line('• On-Time Delivery: ' . $this->performance->on_time_delivery_percentage . '%')
            ->line('• Quality Rating: ' . $this->performance->quality_rating . '/5.0')
            ->line('• Risk Score: ' . $this->performance->risk_score . '/5.0');

        // Add action items if available
        if (!empty($this->performance->action_items)) {
            $mailMessage->line('Recommended Actions:');
            foreach ($this->performance->action_items as $action) {
                $mailMessage->line('• ' . $action);
            }
        }

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->getTitle(),
            'message' => $this->getMessage(),
            'type' => $this->alertType,
            'vendor_id' => $this->performance->vendor_id,
            'vendor_name' => $this->performance->vendor->name,
            'performance_id' => $this->performance->id,
            'performance_score' => $this->performance->performance_score,
            'risk_score' => $this->performance->risk_score,
            'evaluation_date' => $this->performance->evaluation_date->toDateString(),
            'action_url' => $this->getActionUrl(),
            'priority' => $this->getPriority(),
            'additional_data' => $this->additionalData
        ];
    }

    /**
     * Get notification title
     */
    private function getTitle()
    {
        switch ($this->alertType) {
            case 'poor_performance':
                return 'Poor Vendor Performance Alert';
            case 'high_risk':
                return 'High Risk Vendor Alert';
            case 'evaluation_due':
                return 'Vendor Evaluation Due';
            case 'performance_improved':
                return 'Vendor Performance Improved';
            case 'evaluation_completed':
                return 'Vendor Evaluation Completed';
            default:
                return 'Vendor Performance Update';
        }
    }

    /**
     * Get notification message
     */
    private function getMessage()
    {
        $vendorName = $this->performance->vendor->name;
        
        switch ($this->alertType) {
            case 'poor_performance':
                return "Vendor {$vendorName} has received a poor performance rating of {$this->performance->performance_score}/5.0. Immediate attention required.";
            
            case 'high_risk':
                return "Vendor {$vendorName} has been flagged as high risk with a risk score of {$this->performance->risk_score}/5.0. Review and mitigation actions needed.";
            
            case 'evaluation_due':
                $daysOverdue = $this->performance->getDaysUntilNextEvaluation();
                if ($daysOverdue < 0) {
                    return "Vendor {$vendorName} evaluation is overdue by " . abs($daysOverdue) . " days.";
                } else {
                    return "Vendor {$vendorName} evaluation is due in {$daysOverdue} days.";
                }
            
            case 'performance_improved':
                return "Vendor {$vendorName} performance has improved to {$this->performance->performance_score}/5.0.";
            
            case 'evaluation_completed':
                return "Performance evaluation for vendor {$vendorName} has been completed with a score of {$this->performance->performance_score}/5.0.";
            
            default:
                return "Performance update for vendor {$vendorName}.";
        }
    }

    /**
     * Get email subject
     */
    private function getSubject()
    {
        $vendorName = $this->performance->vendor->name;
        
        switch ($this->alertType) {
            case 'poor_performance':
                return "⚠️ Poor Performance Alert - {$vendorName}";
            case 'high_risk':
                return "🚨 High Risk Vendor Alert - {$vendorName}";
            case 'evaluation_due':
                return "📋 Vendor Evaluation Due - {$vendorName}";
            case 'performance_improved':
                return "✅ Performance Improved - {$vendorName}";
            case 'evaluation_completed':
                return "📊 Evaluation Completed - {$vendorName}";
            default:
                return "Vendor Performance Update - {$vendorName}";
        }
    }

    /**
     * Get greeting
     */
    private function getGreeting()
    {
        switch ($this->alertType) {
            case 'poor_performance':
            case 'high_risk':
                return 'Attention Required!';
            case 'evaluation_due':
                return 'Action Needed!';
            case 'performance_improved':
                return 'Good News!';
            case 'evaluation_completed':
                return 'Update!';
            default:
                return 'Hello!';
        }
    }

    /**
     * Get action URL
     */
    private function getActionUrl()
    {
        return url("/vendor-management/vendors/{$this->performance->vendor_id}/performance/{$this->performance->id}");
    }

    /**
     * Get notification priority
     */
    private function getPriority()
    {
        switch ($this->alertType) {
            case 'poor_performance':
            case 'high_risk':
                return 'high';
            case 'evaluation_due':
                return $this->performance->getDaysUntilNextEvaluation() < 0 ? 'high' : 'medium';
            case 'performance_improved':
            case 'evaluation_completed':
                return 'low';
            default:
                return 'medium';
        }
    }

    /**
     * Static methods for creating specific notification types
     */
    public static function poorPerformance(VendorPerformance $performance)
    {
        return new static($performance, 'poor_performance');
    }

    public static function highRisk(VendorPerformance $performance)
    {
        return new static($performance, 'high_risk');
    }

    public static function evaluationDue(VendorPerformance $performance)
    {
        return new static($performance, 'evaluation_due');
    }

    public static function performanceImproved(VendorPerformance $performance)
    {
        return new static($performance, 'performance_improved');
    }

    public static function evaluationCompleted(VendorPerformance $performance)
    {
        return new static($performance, 'evaluation_completed');
    }
}