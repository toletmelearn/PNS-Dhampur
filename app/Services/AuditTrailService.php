<?php

namespace App\Services;

use App\Models\StudentVerification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditTrailService
{
    /**
     * Log a verification activity
     */
    public function logActivity(
        StudentVerification $verification,
        string $action,
        array $details = [],
        ?User $user = null,
        ?array $oldData = null,
        ?array $newData = null
    ): void {
        $user = $user ?? Auth::user();
        
        DB::table('verification_audit_logs')->insert([
            'verification_id' => $verification->id,
            'student_id' => $verification->student_id,
            'user_id' => $user?->id,
            'action' => $action,
            'details' => json_encode($details),
            'old_data' => $oldData ? json_encode($oldData) : null,
            'new_data' => $newData ? json_encode($newData) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Log verification creation
     */
    public function logVerificationCreated(StudentVerification $verification, array $uploadDetails = []): void
    {
        $this->logActivity(
            $verification,
            'verification_created',
            array_merge([
                'verification_type' => $verification->verification_type,
                'document_type' => $verification->document_type,
                'file_name' => $verification->file_name,
                'file_size' => $verification->file_size,
            ], $uploadDetails),
            null,
            null,
            $verification->toArray()
        );
    }

    /**
     * Log verification status change
     */
    public function logStatusChange(
        StudentVerification $verification,
        string $oldStatus,
        string $newStatus,
        ?string $reason = null,
        ?string $comments = null
    ): void {
        $this->logActivity(
            $verification,
            'status_changed',
            [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'comments' => $comments,
            ],
            null,
            ['verification_status' => $oldStatus],
            ['verification_status' => $newStatus]
        );
    }

    /**
     * Log verification approval
     */
    public function logVerificationApproved(
        StudentVerification $verification,
        ?string $comments = null,
        ?array $additionalData = []
    ): void {
        $this->logActivity(
            $verification,
            'verification_approved',
            array_merge([
                'comments' => $comments,
                'confidence_score' => $verification->confidence_score,
            ], $additionalData)
        );
    }

    /**
     * Log verification rejection
     */
    public function logVerificationRejected(
        StudentVerification $verification,
        string $reason,
        ?string $comments = null,
        ?array $additionalData = []
    ): void {
        $this->logActivity(
            $verification,
            'verification_rejected',
            array_merge([
                'reason' => $reason,
                'comments' => $comments,
                'confidence_score' => $verification->confidence_score,
            ], $additionalData)
        );
    }

    /**
     * Log OCR processing
     */
    public function logOcrProcessing(
        StudentVerification $verification,
        string $ocrType,
        array $extractedData,
        float $confidenceScore,
        ?array $errors = []
    ): void {
        $this->logActivity(
            $verification,
            'ocr_processed',
            [
                'ocr_type' => $ocrType,
                'extracted_data' => $extractedData,
                'confidence_score' => $confidenceScore,
                'errors' => $errors,
                'processing_time' => microtime(true) - (session('ocr_start_time', microtime(true))),
            ]
        );
    }

    /**
     * Log mismatch analysis
     */
    public function logMismatchAnalysis(
        StudentVerification $verification,
        array $mismatches,
        string $recommendation,
        float $overallConfidence
    ): void {
        $this->logActivity(
            $verification,
            'mismatch_analyzed',
            [
                'mismatches_count' => count($mismatches),
                'mismatches' => $mismatches,
                'recommendation' => $recommendation,
                'overall_confidence' => $overallConfidence,
            ]
        );
    }

    /**
     * Log automatic resolution
     */
    public function logAutomaticResolution(
        StudentVerification $verification,
        string $resolution,
        array $appliedChanges,
        float $confidenceScore
    ): void {
        $this->logActivity(
            $verification,
            'automatic_resolution',
            [
                'resolution' => $resolution,
                'applied_changes' => $appliedChanges,
                'confidence_score' => $confidenceScore,
                'automated' => true,
            ]
        );
    }

    /**
     * Log manual resolution
     */
    public function logManualResolution(
        StudentVerification $verification,
        string $resolution,
        string $reason,
        ?string $comments = null
    ): void {
        $this->logActivity(
            $verification,
            'manual_resolution',
            [
                'resolution' => $resolution,
                'reason' => $reason,
                'comments' => $comments,
                'automated' => false,
            ]
        );
    }

    /**
     * Log bulk operation
     */
    public function logBulkOperation(
        string $operation,
        array $verificationIds,
        array $results,
        ?User $user = null
    ): void {
        $user = $user ?? Auth::user();
        
        DB::table('verification_audit_logs')->insert([
            'verification_id' => null, // Bulk operation
            'student_id' => null,
            'user_id' => $user?->id,
            'action' => 'bulk_' . $operation,
            'details' => json_encode([
                'operation' => $operation,
                'verification_ids' => $verificationIds,
                'total_count' => count($verificationIds),
                'results' => $results,
            ]),
            'old_data' => null,
            'new_data' => null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Log document reprocessing
     */
    public function logDocumentReprocessed(
        StudentVerification $verification,
        string $reason,
        ?array $previousData = null
    ): void {
        $this->logActivity(
            $verification,
            'document_reprocessed',
            [
                'reason' => $reason,
                'previous_status' => $previousData['verification_status'] ?? null,
                'previous_confidence' => $previousData['confidence_score'] ?? null,
            ],
            null,
            $previousData,
            $verification->toArray()
        );
    }

    /**
     * Log data comparison
     */
    public function logDataComparison(
        StudentVerification $verification,
        array $comparisonResults,
        array $discrepancies
    ): void {
        $this->logActivity(
            $verification,
            'data_compared',
            [
                'comparison_results' => $comparisonResults,
                'discrepancies' => $discrepancies,
                'discrepancy_count' => count($discrepancies),
            ]
        );
    }

    /**
     * Get audit trail for a verification
     */
    public function getVerificationAuditTrail(int $verificationId, int $limit = 50): array
    {
        $logs = DB::table('verification_audit_logs')
            ->leftJoin('users', 'verification_audit_logs.user_id', '=', 'users.id')
            ->where('verification_id', $verificationId)
            ->select([
                'verification_audit_logs.*',
                'users.name as user_name',
                'users.email as user_email'
            ])
            ->orderBy('verification_audit_logs.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                $log->details = json_decode($log->details, true);
                $log->old_data = $log->old_data ? json_decode($log->old_data, true) : null;
                $log->new_data = $log->new_data ? json_decode($log->new_data, true) : null;
                return $log;
            })
            ->toArray();

        return $logs;
    }

    /**
     * Get audit trail for a student
     */
    public function getStudentAuditTrail(int $studentId, int $limit = 100): array
    {
        $logs = DB::table('verification_audit_logs')
            ->leftJoin('users', 'verification_audit_logs.user_id', '=', 'users.id')
            ->leftJoin('student_verifications', 'verification_audit_logs.verification_id', '=', 'student_verifications.id')
            ->where('verification_audit_logs.student_id', $studentId)
            ->select([
                'verification_audit_logs.*',
                'users.name as user_name',
                'users.email as user_email',
                'student_verifications.verification_type',
                'student_verifications.document_type'
            ])
            ->orderBy('verification_audit_logs.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                $log->details = json_decode($log->details, true);
                $log->old_data = $log->old_data ? json_decode($log->old_data, true) : null;
                $log->new_data = $log->new_data ? json_decode($log->new_data, true) : null;
                return $log;
            })
            ->toArray();

        return $logs;
    }

    /**
     * Get audit statistics
     */
    public function getAuditStatistics(array $filters = []): array
    {
        $query = DB::table('verification_audit_logs');

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $stats = [
            'total_activities' => $query->count(),
            'activities_by_action' => $query->select('action', DB::raw('count(*) as count'))
                ->groupBy('action')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'action')
                ->toArray(),
            'activities_by_user' => DB::table('verification_audit_logs')
                ->leftJoin('users', 'verification_audit_logs.user_id', '=', 'users.id')
                ->select('users.name', DB::raw('count(*) as count'))
                ->groupBy('users.id', 'users.name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->pluck('count', 'name')
                ->toArray(),
            'daily_activity' => $query->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get()
                ->pluck('count', 'date')
                ->toArray(),
        ];

        return $stats;
    }

    /**
     * Export audit trail to CSV
     */
    public function exportAuditTrail(array $filters = []): string
    {
        $query = DB::table('verification_audit_logs')
            ->leftJoin('users', 'verification_audit_logs.user_id', '=', 'users.id')
            ->leftJoin('students', 'verification_audit_logs.student_id', '=', 'students.id')
            ->select([
                'verification_audit_logs.created_at',
                'verification_audit_logs.action',
                'users.name as user_name',
                'students.name as student_name',
                'students.student_id',
                'verification_audit_logs.details',
                'verification_audit_logs.ip_address'
            ]);

        // Apply filters
        if (!empty($filters['date_from'])) {
            $query->where('verification_audit_logs.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('verification_audit_logs.created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('verification_audit_logs.user_id', $filters['user_id']);
        }

        $logs = $query->orderBy('verification_audit_logs.created_at', 'desc')->get();

        $filename = 'audit_trail_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/exports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');
        
        // Write CSV header
        fputcsv($file, [
            'Date/Time',
            'Action',
            'User',
            'Student Name',
            'Student ID',
            'Details',
            'IP Address'
        ]);

        // Write data rows
        foreach ($logs as $log) {
            fputcsv($file, [
                $log->created_at,
                $log->action,
                $log->user_name ?? 'System',
                $log->student_name ?? 'N/A',
                $log->student_id ?? 'N/A',
                $log->details,
                $log->ip_address
            ]);
        }

        fclose($file);

        return $filepath;
    }

    /**
     * Clean old audit logs
     */
    public function cleanOldLogs(int $daysToKeep = 365): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return DB::table('verification_audit_logs')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
}