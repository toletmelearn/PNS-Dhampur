<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class PerformanceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $alertType;
    protected $metricData;
    protected $threshold;
    protected $currentValue;
    protected $severity;
    protected $additionalData;

    /**
     * Create a new notification instance.
     */
    public function __construct($alertType, $metricData, $threshold = null, $currentValue = null, $severity = 'warning', $additionalData = [])
    {
        $this->alertType = $alertType;
        $this->metricData = $metricData;
        $this->threshold = $threshold;
        $this->currentValue = $currentValue;
        $this->severity = $severity;
        $this->additionalData = $additionalData;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        $channels = ['database'];
        
        // Add email for critical alerts
        if ($this->severity === 'critical') {
            $channels[] = 'mail';
        }
        
        // Add SMS for emergency alerts if configured
        if ($this->severity === 'emergency' && config('notifications.sms_enabled')) {
            $channels[] = 'sms';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject($this->getEmailSubject())
            ->greeting($this->getEmailGreeting())
            ->line($this->getMessage())
            ->line($this->getMetricDetails())
            ->action('View Performance Dashboard', url('/admin/performance'))
            ->line('Please investigate and take appropriate action.');

        // Set priority based on severity
        if ($this->severity === 'critical' || $this->severity === 'emergency') {
            $mailMessage->error();
        } elseif ($this->severity === 'warning') {
            $mailMessage->warning();
        } else {
            $mailMessage->info();
        }

        // Add recommendations if available
        if (!empty($this->additionalData['recommendations'])) {
            $mailMessage->line('Recommended Actions:');
            foreach ($this->additionalData['recommendations'] as $recommendation) {
                $mailMessage->line('• ' . $recommendation);
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
            'severity' => $this->severity,
            'metric_data' => $this->metricData,
            'threshold' => $this->threshold,
            'current_value' => $this->currentValue,
            'action_url' => url('/admin/performance'),
            'priority' => $this->getPriority(),
            'additional_data' => $this->additionalData,
            'alert_time' => Carbon::now()->toDateTimeString()
        ];
    }

    /**
     * Get notification title
     */
    private function getTitle()
    {
        switch ($this->alertType) {
            case 'high_response_time':
                return 'High Response Time Alert';
            case 'high_memory_usage':
                return 'High Memory Usage Alert';
            case 'high_cpu_usage':
                return 'High CPU Usage Alert';
            case 'disk_space_low':
                return 'Low Disk Space Alert';
            case 'database_slow_queries':
                return 'Slow Database Queries Alert';
            case 'high_error_rate':
                return 'High Error Rate Alert';
            case 'system_overload':
                return 'System Overload Alert';
            case 'service_unavailable':
                return 'Service Unavailable Alert';
            case 'backup_failure':
                return 'Backup Failure Alert';
            case 'security_breach':
                return 'Security Breach Alert';
            default:
                return 'Performance Alert';
        }
    }

    /**
     * Get notification message
     */
    private function getMessage()
    {
        switch ($this->alertType) {
            case 'high_response_time':
                return "System response time has exceeded the threshold. Current: {$this->currentValue}ms, Threshold: {$this->threshold}ms";
            
            case 'high_memory_usage':
                return "Memory usage is critically high. Current: {$this->currentValue}%, Threshold: {$this->threshold}%";
            
            case 'high_cpu_usage':
                return "CPU usage has exceeded normal levels. Current: {$this->currentValue}%, Threshold: {$this->threshold}%";
            
            case 'disk_space_low':
                return "Disk space is running low. Available: {$this->currentValue}GB, Minimum required: {$this->threshold}GB";
            
            case 'database_slow_queries':
                return "Database queries are running slower than expected. Average query time: {$this->currentValue}ms";
            
            case 'high_error_rate':
                return "Error rate has increased significantly. Current rate: {$this->currentValue}%, Threshold: {$this->threshold}%";
            
            case 'system_overload':
                return "System is experiencing high load. Current load: {$this->currentValue}, Threshold: {$this->threshold}";
            
            case 'service_unavailable':
                return "Critical service is unavailable: {$this->metricData['service_name']}";
            
            case 'backup_failure':
                return "Automated backup has failed. Last successful backup: {$this->metricData['last_backup']}";
            
            case 'security_breach':
                return "Potential security breach detected. Immediate attention required.";
            
            default:
                return "Performance issue detected that requires attention.";
        }
    }

    /**
     * Get email subject
     */
    private function getEmailSubject()
    {
        $severityIcon = $this->getSeverityIcon();
        $title = $this->getTitle();
        
        return "{$severityIcon} {$title} - PNS Dhampur System";
    }

    /**
     * Get email greeting
     */
    private function getEmailGreeting()
    {
        switch ($this->severity) {
            case 'emergency':
                return 'URGENT: Immediate Action Required!';
            case 'critical':
                return 'Critical Alert!';
            case 'warning':
                return 'Performance Warning!';
            default:
                return 'Performance Notification';
        }
    }

    /**
     * Get metric details for email
     */
    private function getMetricDetails()
    {
        $details = "Alert Details:\n";
        $details .= "• Alert Type: " . ucwords(str_replace('_', ' ', $this->alertType)) . "\n";
        $details .= "• Severity: " . ucfirst($this->severity) . "\n";
        $details .= "• Time: " . Carbon::now()->format('Y-m-d H:i:s') . "\n";
        
        if ($this->currentValue) {
            $details .= "• Current Value: {$this->currentValue}\n";
        }
        
        if ($this->threshold) {
            $details .= "• Threshold: {$this->threshold}\n";
        }
        
        if (!empty($this->metricData)) {
            foreach ($this->metricData as $key => $value) {
                if (!in_array($key, ['service_name', 'last_backup'])) {
                    $details .= "• " . ucwords(str_replace('_', ' ', $key)) . ": {$value}\n";
                }
            }
        }
        
        return $details;
    }

    /**
     * Get severity icon
     */
    private function getSeverityIcon()
    {
        switch ($this->severity) {
            case 'emergency':
                return '🚨';
            case 'critical':
                return '⚠️';
            case 'warning':
                return '⚡';
            default:
                return 'ℹ️';
        }
    }

    /**
     * Get priority level
     */
    private function getPriority()
    {
        switch ($this->severity) {
            case 'emergency':
                return 'urgent';
            case 'critical':
                return 'high';
            case 'warning':
                return 'medium';
            default:
                return 'low';
        }
    }

    /**
     * Static methods for creating specific alert types
     */
    public static function highResponseTime($currentTime, $threshold = 5000)
    {
        return new static('high_response_time', [], $threshold, $currentTime, 'warning');
    }

    public static function highMemoryUsage($currentUsage, $threshold = 85)
    {
        return new static('high_memory_usage', [], $threshold, $currentUsage, 'critical');
    }

    public static function highCpuUsage($currentUsage, $threshold = 80)
    {
        return new static('high_cpu_usage', [], $threshold, $currentUsage, 'warning');
    }

    public static function diskSpaceLow($availableSpace, $threshold = 10)
    {
        return new static('disk_space_low', [], $threshold, $availableSpace, 'critical');
    }

    public static function databaseSlowQueries($avgQueryTime)
    {
        return new static('database_slow_queries', [], null, $avgQueryTime, 'warning');
    }

    public static function highErrorRate($currentRate, $threshold = 5)
    {
        return new static('high_error_rate', [], $threshold, $currentRate, 'critical');
    }

    public static function systemOverload($currentLoad, $threshold = 10)
    {
        return new static('system_overload', [], $threshold, $currentLoad, 'critical');
    }

    public static function serviceUnavailable($serviceName)
    {
        return new static('service_unavailable', ['service_name' => $serviceName], null, null, 'emergency');
    }

    public static function backupFailure($lastBackup)
    {
        return new static('backup_failure', ['last_backup' => $lastBackup], null, null, 'critical');
    }

    public static function securityBreach($details = [])
    {
        return new static('security_breach', $details, null, null, 'emergency');
    }
}