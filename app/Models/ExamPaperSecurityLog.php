<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExamPaperSecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_paper_id',
        'exam_paper_version_id',
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'session_id',
        'severity',
        'risk_level',
        'is_suspicious',
        'requires_investigation',
        'investigated_at',
        'investigated_by',
        'investigation_notes',
        'metadata',
        'checksum'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'is_suspicious' => 'boolean',
        'requires_investigation' => 'boolean',
        'investigated_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    protected $dates = [
        'investigated_at',
        'created_at'
    ];

    // Action constants
    const ACTION_CREATED = 'created';
    const ACTION_VIEWED = 'viewed';
    const ACTION_EDITED = 'edited';
    const ACTION_DELETED = 'deleted';
    const ACTION_SUBMITTED = 'submitted';
    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';
    const ACTION_DOWNLOADED = 'downloaded';
    const ACTION_PRINTED = 'printed';
    const ACTION_EXPORTED = 'exported';
    const ACTION_COPIED = 'copied';
    const ACTION_SHARED = 'shared';
    const ACTION_ACCESSED_UNAUTHORIZED = 'accessed_unauthorized';
    const ACTION_LOGIN_ATTEMPT = 'login_attempt';
    const ACTION_PERMISSION_DENIED = 'permission_denied';
    const ACTION_DATA_BREACH_ATTEMPT = 'data_breach_attempt';
    const ACTION_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    // Severity constants
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Risk level constants
    const RISK_NONE = 'none';
    const RISK_LOW = 'low';
    const RISK_MEDIUM = 'medium';
    const RISK_HIGH = 'high';
    const RISK_CRITICAL = 'critical';

    /**
     * Relationships
     */
    public function examPaper(): BelongsTo
    {
        return $this->belongsTo(ExamPaper::class);
    }

    public function examPaperVersion(): BelongsTo
    {
        return $this->belongsTo(ExamPaperVersion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by');
    }

    /**
     * Scopes
     */
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByRiskLevel($query, $riskLevel)
    {
        return $query->where('risk_level', $riskLevel);
    }

    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious', true);
    }

    public function scopeRequiresInvestigation($query)
    {
        return $query->where('requires_investigation', true)
                    ->whereNull('investigated_at');
    }

    public function scopeInvestigated($query)
    {
        return $query->whereNotNull('investigated_at');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByIpAddress($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    public function scopeByDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL)
                    ->orWhere('risk_level', self::RISK_CRITICAL);
    }

    /**
     * Static Methods for Logging
     */
    public static function logActivity(array $data): self
    {
        // Auto-populate common fields
        $logData = array_merge([
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'severity' => self::SEVERITY_MEDIUM,
            'risk_level' => self::RISK_NONE,
            'is_suspicious' => false,
            'requires_investigation' => false
        ], $data);

        // Analyze for suspicious activity
        $logData = self::analyzeSuspiciousActivity($logData);

        // Generate checksum for integrity
        $logData['checksum'] = self::generateChecksum($logData);

        return self::create($logData);
    }

    public static function logExamPaperAccess(int $examPaperId, string $action, string $description = null): self
    {
        return self::logActivity([
            'exam_paper_id' => $examPaperId,
            'action' => $action,
            'resource_type' => 'exam_paper',
            'resource_id' => $examPaperId,
            'description' => $description ?? "Exam paper {$action}",
            'severity' => self::getSeverityForAction($action),
            'risk_level' => self::getRiskLevelForAction($action)
        ]);
    }

    public static function logVersionAccess(int $versionId, string $action, string $description = null): self
    {
        $version = ExamPaperVersion::find($versionId);
        
        return self::logActivity([
            'exam_paper_id' => $version->exam_paper_id,
            'exam_paper_version_id' => $versionId,
            'action' => $action,
            'resource_type' => 'exam_paper_version',
            'resource_id' => $versionId,
            'description' => $description ?? "Exam paper version {$action}",
            'severity' => self::getSeverityForAction($action),
            'risk_level' => self::getRiskLevelForAction($action)
        ]);
    }

    public static function logSecurityIncident(string $action, string $description, array $metadata = []): self
    {
        return self::logActivity([
            'action' => $action,
            'resource_type' => 'security_incident',
            'description' => $description,
            'severity' => self::SEVERITY_HIGH,
            'risk_level' => self::RISK_HIGH,
            'is_suspicious' => true,
            'requires_investigation' => true,
            'metadata' => $metadata
        ]);
    }

    public static function logDataChange(string $resourceType, int $resourceId, array $oldValues, array $newValues, string $description = null): self
    {
        return self::logActivity([
            'action' => self::ACTION_EDITED,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'description' => $description ?? "Data changed for {$resourceType}",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'severity' => self::SEVERITY_MEDIUM,
            'risk_level' => self::RISK_LOW
        ]);
    }

    /**
     * Security Analysis Methods
     */
    protected static function analyzeSuspiciousActivity(array $logData): array
    {
        $suspiciousIndicators = 0;
        $riskFactors = [];

        // Check for multiple failed attempts
        if (in_array($logData['action'], [self::ACTION_LOGIN_ATTEMPT, self::ACTION_PERMISSION_DENIED])) {
            $recentFailures = self::where('user_id', $logData['user_id'])
                ->where('action', $logData['action'])
                ->where('created_at', '>', now()->subMinutes(15))
                ->count();

            if ($recentFailures >= 3) {
                $suspiciousIndicators++;
                $riskFactors[] = 'Multiple failed attempts';
            }
        }

        // Check for unusual access patterns
        if (in_array($logData['action'], [self::ACTION_VIEWED, self::ACTION_DOWNLOADED, self::ACTION_EXPORTED])) {
            $recentAccess = self::where('user_id', $logData['user_id'])
                ->where('action', $logData['action'])
                ->where('created_at', '>', now()->subHour())
                ->count();

            if ($recentAccess >= 10) {
                $suspiciousIndicators++;
                $riskFactors[] = 'Unusual access frequency';
            }
        }

        // Check for access from different IP addresses
        if (isset($logData['user_id'])) {
            $recentIPs = self::where('user_id', $logData['user_id'])
                ->where('created_at', '>', now()->subHours(2))
                ->distinct('ip_address')
                ->count();

            if ($recentIPs >= 3) {
                $suspiciousIndicators++;
                $riskFactors[] = 'Multiple IP addresses';
            }
        }

        // Check for off-hours access
        $currentHour = now()->hour;
        if ($currentHour < 6 || $currentHour > 22) {
            $suspiciousIndicators++;
            $riskFactors[] = 'Off-hours access';
        }

        // Update risk assessment based on indicators
        if ($suspiciousIndicators >= 3) {
            $logData['is_suspicious'] = true;
            $logData['requires_investigation'] = true;
            $logData['risk_level'] = self::RISK_HIGH;
            $logData['severity'] = self::SEVERITY_HIGH;
        } elseif ($suspiciousIndicators >= 2) {
            $logData['is_suspicious'] = true;
            $logData['risk_level'] = self::RISK_MEDIUM;
            $logData['severity'] = self::SEVERITY_MEDIUM;
        } elseif ($suspiciousIndicators >= 1) {
            $logData['risk_level'] = self::RISK_LOW;
        }

        // Add risk factors to metadata
        if (!empty($riskFactors)) {
            $logData['metadata'] = array_merge($logData['metadata'] ?? [], [
                'risk_factors' => $riskFactors,
                'suspicious_indicators' => $suspiciousIndicators
            ]);
        }

        return $logData;
    }

    protected static function getSeverityForAction(string $action): string
    {
        return match($action) {
            self::ACTION_CREATED, self::ACTION_VIEWED => self::SEVERITY_LOW,
            self::ACTION_EDITED, self::ACTION_SUBMITTED, self::ACTION_DOWNLOADED => self::SEVERITY_MEDIUM,
            self::ACTION_DELETED, self::ACTION_APPROVED, self::ACTION_REJECTED => self::SEVERITY_HIGH,
            self::ACTION_ACCESSED_UNAUTHORIZED, self::ACTION_DATA_BREACH_ATTEMPT, 
            self::ACTION_SUSPICIOUS_ACTIVITY => self::SEVERITY_CRITICAL,
            default => self::SEVERITY_MEDIUM
        };
    }

    protected static function getRiskLevelForAction(string $action): string
    {
        return match($action) {
            self::ACTION_VIEWED => self::RISK_NONE,
            self::ACTION_CREATED, self::ACTION_EDITED => self::RISK_LOW,
            self::ACTION_DOWNLOADED, self::ACTION_EXPORTED, self::ACTION_PRINTED => self::RISK_MEDIUM,
            self::ACTION_DELETED, self::ACTION_SHARED, self::ACTION_COPIED => self::RISK_HIGH,
            self::ACTION_ACCESSED_UNAUTHORIZED, self::ACTION_DATA_BREACH_ATTEMPT => self::RISK_CRITICAL,
            default => self::RISK_LOW
        };
    }

    protected static function generateChecksum(array $data): string
    {
        // Remove checksum field if present to avoid circular reference
        unset($data['checksum']);
        
        return hash('sha256', json_encode($data) . config('app.key'));
    }

    /**
     * Investigation Methods
     */
    public function investigate(User $investigator, string $notes): bool
    {
        $this->update([
            'investigated_at' => now(),
            'investigated_by' => $investigator->id,
            'investigation_notes' => $notes,
            'requires_investigation' => false
        ]);

        // Log the investigation
        self::logActivity([
            'action' => 'investigated',
            'resource_type' => 'security_log',
            'resource_id' => $this->id,
            'description' => "Security log investigated by {$investigator->name}",
            'metadata' => [
                'original_log_id' => $this->id,
                'investigation_notes' => $notes
            ]
        ]);

        return true;
    }

    public function markAsResolved(string $resolution): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata['resolution'] = $resolution;
        $metadata['resolved_at'] = now();
        $metadata['resolved_by'] = Auth::id();

        $this->update([
            'metadata' => $metadata,
            'requires_investigation' => false
        ]);

        return true;
    }

    /**
     * Utility Methods
     */
    public function verifyIntegrity(): bool
    {
        $data = $this->toArray();
        unset($data['checksum']);
        
        $expectedChecksum = hash('sha256', json_encode($data) . config('app.key'));
        return hash_equals($this->checksum, $expectedChecksum);
    }

    public function getSeverityBadgeClass(): string
    {
        return match($this->severity) {
            self::SEVERITY_LOW => 'badge-success',
            self::SEVERITY_MEDIUM => 'badge-warning',
            self::SEVERITY_HIGH => 'badge-danger',
            self::SEVERITY_CRITICAL => 'badge-dark',
            default => 'badge-light'
        };
    }

    public function getRiskBadgeClass(): string
    {
        return match($this->risk_level) {
            self::RISK_NONE => 'badge-light',
            self::RISK_LOW => 'badge-success',
            self::RISK_MEDIUM => 'badge-warning',
            self::RISK_HIGH => 'badge-danger',
            self::RISK_CRITICAL => 'badge-dark',
            default => 'badge-light'
        };
    }

    public function getActionBadgeClass(): string
    {
        return match($this->action) {
            self::ACTION_CREATED, self::ACTION_VIEWED => 'badge-info',
            self::ACTION_EDITED, self::ACTION_SUBMITTED => 'badge-primary',
            self::ACTION_APPROVED => 'badge-success',
            self::ACTION_REJECTED, self::ACTION_DELETED => 'badge-danger',
            self::ACTION_DOWNLOADED, self::ACTION_EXPORTED, self::ACTION_PRINTED => 'badge-warning',
            self::ACTION_ACCESSED_UNAUTHORIZED, self::ACTION_DATA_BREACH_ATTEMPT => 'badge-dark',
            default => 'badge-secondary'
        };
    }

    public function getFormattedAction(): string
    {
        return ucfirst(str_replace('_', ' ', $this->action));
    }

    public function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Static Analytics Methods
     */
    public static function getSecurityStatistics(): array
    {
        return [
            'total_logs' => self::count(),
            'suspicious_activities' => self::suspicious()->count(),
            'pending_investigations' => self::requiresInvestigation()->count(),
            'critical_incidents' => self::critical()->count(),
            'high_risk_activities' => self::highRisk()->count(),
            'unique_users' => self::distinct('user_id')->count(),
            'unique_ip_addresses' => self::distinct('ip_address')->count()
        ];
    }

    public static function getActivityTrends(int $days = 30): array
    {
        return self::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public static function getTopRiskyUsers(int $limit = 10): array
    {
        return self::selectRaw('user_id, users.name, COUNT(*) as incident_count, 
                               AVG(CASE 
                                   WHEN risk_level = "critical" THEN 5
                                   WHEN risk_level = "high" THEN 4
                                   WHEN risk_level = "medium" THEN 3
                                   WHEN risk_level = "low" THEN 2
                                   ELSE 1
                               END) as avg_risk_score')
            ->join('users', 'users.id', '=', 'exam_paper_security_logs.user_id')
            ->where('created_at', '>', now()->subDays(30))
            ->groupBy('user_id', 'users.name')
            ->orderByDesc('avg_risk_score')
            ->orderByDesc('incident_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}