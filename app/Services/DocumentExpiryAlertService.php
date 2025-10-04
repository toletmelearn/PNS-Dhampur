<?php

namespace App\Services;

use App\Models\TeacherDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class DocumentExpiryAlertService
{
    /**
     * Alert thresholds in days
     */
    const ALERT_THRESHOLDS = [
        'critical' => 7,    // 1 week
        'warning' => 30,    // 1 month
        'notice' => 90      // 3 months
    ];

    /**
     * Get documents expiring within specified days
     */
    public function getExpiringDocuments($days = 30)
    {
        return TeacherDocument::with(['teacher.user'])
            ->where('status', TeacherDocument::STATUS_VERIFIED)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays($days))
            ->where('expiry_date', '>=', Carbon::now())
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get expired documents
     */
    public function getExpiredDocuments()
    {
        return TeacherDocument::with(['teacher.user'])
            ->where('status', TeacherDocument::STATUS_VERIFIED)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::now())
            ->where('is_expired', false)
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get documents by alert level
     */
    public function getDocumentsByAlertLevel($level = 'all')
    {
        $documents = collect();
        
        switch ($level) {
            case 'critical':
                $documents = $this->getExpiringDocuments(self::ALERT_THRESHOLDS['critical']);
                break;
            case 'warning':
                $documents = $this->getExpiringDocuments(self::ALERT_THRESHOLDS['warning'])
                    ->filter(function ($doc) {
                        return $doc->expiry_date->diffInDays(Carbon::now()) > self::ALERT_THRESHOLDS['critical'];
                    });
                break;
            case 'notice':
                $documents = $this->getExpiringDocuments(self::ALERT_THRESHOLDS['notice'])
                    ->filter(function ($doc) {
                        return $doc->expiry_date->diffInDays(Carbon::now()) > self::ALERT_THRESHOLDS['warning'];
                    });
                break;
            case 'expired':
                $documents = $this->getExpiredDocuments();
                break;
            default:
                $documents = $this->getExpiringDocuments(self::ALERT_THRESHOLDS['notice']);
                break;
        }

        return $documents->map(function ($doc) {
            return $this->formatDocumentAlert($doc);
        });
    }

    /**
     * Format document for alert display
     */
    private function formatDocumentAlert($document)
    {
        $now = Carbon::now();
        $expiryDate = $document->expiry_date;
        $daysUntilExpiry = $expiryDate->diffInDays($now);
        $isExpired = $expiryDate->isPast();

        return [
            'id' => $document->id,
            'teacher_id' => $document->teacher_id,
            'teacher_name' => $document->teacher->user->name,
            'teacher_email' => $document->teacher->user->email,
            'document_type' => $document->document_type,
            'document_type_label' => TeacherDocument::DOCUMENT_TYPES[$document->document_type] ?? $document->document_type,
            'expiry_date' => $expiryDate->format('Y-m-d'),
            'expiry_date_formatted' => $expiryDate->format('d M Y'),
            'days_until_expiry' => $isExpired ? 0 : $daysUntilExpiry,
            'is_expired' => $isExpired,
            'alert_level' => $this->getAlertLevel($daysUntilExpiry, $isExpired),
            'status_message' => $this->getStatusMessage($daysUntilExpiry, $isExpired),
            'urgency_score' => $this->calculateUrgencyScore($daysUntilExpiry, $isExpired),
            'file_path' => $document->file_path,
            'uploaded_at' => $document->created_at->format('Y-m-d'),
        ];
    }

    /**
     * Get alert level based on days until expiry
     */
    private function getAlertLevel($daysUntilExpiry, $isExpired)
    {
        if ($isExpired) {
            return 'expired';
        }

        if ($daysUntilExpiry <= self::ALERT_THRESHOLDS['critical']) {
            return 'critical';
        }

        if ($daysUntilExpiry <= self::ALERT_THRESHOLDS['warning']) {
            return 'warning';
        }

        if ($daysUntilExpiry <= self::ALERT_THRESHOLDS['notice']) {
            return 'notice';
        }

        return 'normal';
    }

    /**
     * Get status message for document
     */
    private function getStatusMessage($daysUntilExpiry, $isExpired)
    {
        if ($isExpired) {
            return 'Document has expired';
        }

        if ($daysUntilExpiry == 0) {
            return 'Expires today';
        }

        if ($daysUntilExpiry == 1) {
            return 'Expires tomorrow';
        }

        if ($daysUntilExpiry <= 7) {
            return "Expires in {$daysUntilExpiry} days";
        }

        if ($daysUntilExpiry <= 30) {
            $weeks = ceil($daysUntilExpiry / 7);
            return "Expires in {$weeks} week" . ($weeks > 1 ? 's' : '');
        }

        $months = ceil($daysUntilExpiry / 30);
        return "Expires in {$months} month" . ($months > 1 ? 's' : '');
    }

    /**
     * Calculate urgency score (0-100, higher = more urgent)
     */
    private function calculateUrgencyScore($daysUntilExpiry, $isExpired)
    {
        if ($isExpired) {
            return 100;
        }

        if ($daysUntilExpiry <= self::ALERT_THRESHOLDS['critical']) {
            return 90 + (self::ALERT_THRESHOLDS['critical'] - $daysUntilExpiry) * 2;
        }

        if ($daysUntilExpiry <= self::ALERT_THRESHOLDS['warning']) {
            return 60 + (self::ALERT_THRESHOLDS['warning'] - $daysUntilExpiry);
        }

        if ($daysUntilExpiry <= self::ALERT_THRESHOLDS['notice']) {
            return 30 + (self::ALERT_THRESHOLDS['notice'] - $daysUntilExpiry) * 0.5;
        }

        return 0;
    }

    /**
     * Process expiry alerts and mark expired documents
     */
    public function processExpiryAlerts()
    {
        $results = [
            'expired_marked' => 0,
            'alerts_generated' => 0,
            'notifications_sent' => 0,
            'errors' => []
        ];

        try {
            // Mark expired documents
            $expiredDocuments = $this->getExpiredDocuments();
            foreach ($expiredDocuments as $document) {
                $document->update(['is_expired' => true]);
                $results['expired_marked']++;
                
                Log::info("Marked document as expired", [
                    'document_id' => $document->id,
                    'teacher' => $document->teacher->user->name,
                    'document_type' => $document->document_type,
                    'expiry_date' => $document->expiry_date->format('Y-m-d')
                ]);
            }

            // Generate alerts for expiring documents
            $expiringDocuments = $this->getExpiringDocuments(self::ALERT_THRESHOLDS['notice']);
            foreach ($expiringDocuments as $document) {
                $alertData = $this->formatDocumentAlert($document);
                
                // Only send alerts for critical and warning levels
                if (in_array($alertData['alert_level'], ['critical', 'warning'])) {
                    $this->sendExpiryNotification($document, $alertData);
                    $results['notifications_sent']++;
                }
                
                $results['alerts_generated']++;
            }

        } catch (\Exception $e) {
            $results['errors'][] = $e->getMessage();
            Log::error("Error processing expiry alerts: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Send expiry notification
     */
    private function sendExpiryNotification($document, $alertData)
    {
        try {
            // Send notification to teacher
            $teacher = $document->teacher;
            if ($teacher && $teacher->user && $teacher->user->email) {
                // Here you would typically send an email or push notification
                // For now, we'll log the notification
                Log::info("Expiry notification sent", [
                    'teacher_email' => $teacher->user->email,
                    'document_type' => $alertData['document_type_label'],
                    'alert_level' => $alertData['alert_level'],
                    'days_until_expiry' => $alertData['days_until_expiry']
                ]);
            }

            // Send notification to admin users
            $adminUsers = User::where('role', 'admin')->get();
            foreach ($adminUsers as $admin) {
                Log::info("Admin expiry notification", [
                    'admin_email' => $admin->email,
                    'teacher_name' => $alertData['teacher_name'],
                    'document_type' => $alertData['document_type_label'],
                    'alert_level' => $alertData['alert_level']
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to send expiry notification: " . $e->getMessage());
        }
    }

    /**
     * Get alert statistics
     */
    public function getAlertStatistics()
    {
        $stats = [
            'total_documents' => TeacherDocument::where('status', TeacherDocument::STATUS_VERIFIED)->count(),
            'documents_with_expiry' => TeacherDocument::where('status', TeacherDocument::STATUS_VERIFIED)
                ->whereNotNull('expiry_date')->count(),
            'expired' => $this->getExpiredDocuments()->count(),
            'critical' => $this->getDocumentsByAlertLevel('critical')->count(),
            'warning' => $this->getDocumentsByAlertLevel('warning')->count(),
            'notice' => $this->getDocumentsByAlertLevel('notice')->count(),
        ];

        $stats['healthy'] = $stats['documents_with_expiry'] - $stats['expired'] - 
                           $stats['critical'] - $stats['warning'] - $stats['notice'];

        return $stats;
    }

    /**
     * Get upcoming renewals (documents expiring in next 6 months)
     */
    public function getUpcomingRenewals()
    {
        return TeacherDocument::with(['teacher.user'])
            ->where('status', TeacherDocument::STATUS_VERIFIED)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [
                Carbon::now()->addDays(self::ALERT_THRESHOLDS['notice']),
                Carbon::now()->addMonths(6)
            ])
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function ($doc) {
                return $this->formatDocumentAlert($doc);
            });
    }

    /**
     * Check if service is available
     */
    public function isServiceAvailable()
    {
        try {
            // Basic health check
            $testQuery = TeacherDocument::count();
            return [
                'available' => true,
                'message' => 'Document expiry alert service is operational',
                'total_documents' => $testQuery
            ];
        } catch (\Exception $e) {
            return [
                'available' => false,
                'message' => 'Service unavailable: ' . $e->getMessage()
            ];
        }
    }
}