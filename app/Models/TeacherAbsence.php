<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class TeacherAbsence extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'absence_date',
        'end_date',
        'reason_category',
        'reason_details',
        'status',
        'reported_by',
        'reported_at',
        'approved_by',
        'approved_at',
        'periods_affected',
        'classes_affected',
        'priority',
        'notification_sent',
        'substitute_required',
        'medical_certificate',
        'notes'
    ];

    protected $casts = [
        'absence_date' => 'date',
        'end_date' => 'date',
        'reported_at' => 'datetime',
        'approved_at' => 'datetime',
        'periods_affected' => 'array',
        'classes_affected' => 'array',
        'notification_sent' => 'boolean',
        'substitute_required' => 'boolean'
    ];

    // Absence reason categories
    const REASON_CATEGORIES = [
        'sick_leave' => 'Sick Leave',
        'personal_leave' => 'Personal Leave',
        'emergency' => 'Emergency',
        'medical_appointment' => 'Medical Appointment',
        'family_emergency' => 'Family Emergency',
        'bereavement' => 'Bereavement',
        'maternity_paternity' => 'Maternity/Paternity Leave',
        'professional_development' => 'Professional Development',
        'jury_duty' => 'Jury Duty',
        'other' => 'Other'
    ];

    // Absence status
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Relationship with Teacher model
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Relationship with User who reported the absence
     */
    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Relationship with User who approved the absence
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relationship with substitutions
     */
    public function substitutions()
    {
        return $this->hasMany(TeacherSubstitution::class, 'absence_id');
    }

    /**
     * Get monthly CL/ML summary (counts by day) for a date range.
     * Maps reason_category to CL (personal_leave) and ML (sick_leave, medical_appointment).
     * Defaults to last 6 months up to current month.
     */
    public static function getMonthlyCLMLSummary($startDate = null, $endDate = null, $teacherId = null)
    {
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::now()->endOfMonth();
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->subMonths(6)->startOfMonth();

        $query = self::whereBetween('absence_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->where('status', self::STATUS_APPROVED);

        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        $records = $query->get(['teacher_id','absence_date','end_date','reason_category']);

        $summary = [];

        foreach ($records as $rec) {
            $reason = $rec->reason_category;
            $isCL = ($reason === 'personal_leave');
            $isML = ($reason === 'sick_leave' || $reason === 'medical_appointment');
            if (!$isCL && !$isML) {
                continue; // skip non-CL/ML categories
            }

            $rangeStart = $rec->absence_date->copy()->startOfDay();
            $rangeEnd = $rec->end_date ? Carbon::parse($rec->end_date)->endOfDay() : $rec->absence_date->copy()->endOfDay();

            // Clip to requested window
            if ($rangeEnd < $start || $rangeStart > $end) {
                continue;
            }
            $rangeStart = $rangeStart->max($start);
            $rangeEnd = $rangeEnd->min($end);

            // Iterate per day to attribute to correct month
            $period = CarbonPeriod::create($rangeStart, '1 day', $rangeEnd);
            foreach ($period as $day) {
                $monthKey = $day->format('Y-m');
                if (!isset($summary[$monthKey])) {
                    $summary[$monthKey] = ['CL_days' => 0, 'ML_days' => 0];
                }
                if ($isCL) {
                    $summary[$monthKey]['CL_days'] += 1;
                } elseif ($isML) {
                    $summary[$monthKey]['ML_days'] += 1;
                }
            }
        }

        // Ensure months in range exist with zeros
        $cursor = $start->copy()->startOfMonth();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            if (!isset($summary[$key])) {
                $summary[$key] = ['CL_days' => 0, 'ML_days' => 0];
            }
            $cursor->addMonth();
        }

        ksort($summary);

        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'teacher_id' => $teacherId,
            'months' => $summary,
        ];
    }

    /**
     * Map a general leave_type string to this model's reason_category.
     */
    public static function mapLeaveTypeToReasonCategory(string $leaveType): string
    {
        $lt = strtolower(trim($leaveType));
        $map = [
            'casual' => 'personal_leave',
            'casual_leave' => 'personal_leave',
            'sick' => 'sick_leave',
            'sick_leave' => 'sick_leave',
            'earned' => 'other',
            'maternity' => 'maternity_paternity',
            'paternity' => 'maternity_paternity',
            'emergency' => 'emergency',
            'unpaid' => 'other',
        ];
        return $map[$lt] ?? 'other';
    }

    /**
     * Get today's absences
     */
    public static function getTodayAbsences()
    {
        return self::with(['teacher', 'reportedBy'])
            ->whereDate('absence_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->where('status', self::STATUS_APPROVED)
            ->orderBy('priority', 'desc')
            ->orderBy('reported_at', 'asc')
            ->get();
    }

    /**
     * Get upcoming absences (next 7 days)
     */
    public static function getUpcomingAbsences($days = 7)
    {
        return self::with(['teacher', 'reportedBy'])
            ->whereBetween('absence_date', [Carbon::tomorrow(), Carbon::today()->addDays($days)])
            ->where('status', self::STATUS_APPROVED)
            ->orderBy('absence_date', 'asc')
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Get pending approval absences
     */
    public static function getPendingAbsences()
    {
        return self::with(['teacher', 'reportedBy'])
            ->where('status', self::STATUS_PENDING)
            ->orderBy('priority', 'desc')
            ->orderBy('reported_at', 'asc')
            ->get();
    }

    /**
     * Get absences requiring substitutes
     */
    public static function getAbsencesRequiringSubstitutes()
    {
        return self::with(['teacher', 'substitutions'])
            ->whereDate('absence_date', '<=', Carbon::today())
            ->whereDate('end_date', '>=', Carbon::today())
            ->where('status', self::STATUS_APPROVED)
            ->where('substitute_required', true)
            ->whereDoesntHave('substitutions', function ($query) {
                $query->where('status', 'confirmed');
            })
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Check if absence is active today
     */
    public function isActiveToday()
    {
        $today = Carbon::today();
        return $this->absence_date <= $today && $this->end_date >= $today && $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if absence is multi-day
     */
    public function isMultiDay()
    {
        return $this->absence_date->format('Y-m-d') !== $this->end_date->format('Y-m-d');
    }

    /**
     * Get duration in days
     */
    public function getDurationInDays()
    {
        return $this->absence_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Get affected periods as formatted string
     */
    public function getAffectedPeriodsString()
    {
        if (empty($this->periods_affected)) {
            return 'All periods';
        }
        return implode(', ', $this->periods_affected);
    }

    /**
     * Get affected classes as formatted string
     */
    public function getAffectedClassesString()
    {
        if (empty($this->classes_affected)) {
            return 'All classes';
        }
        return implode(', ', $this->classes_affected);
    }

    /**
     * Get priority color for UI
     */
    public function getPriorityColor()
    {
        switch ($this->priority) {
            case self::PRIORITY_URGENT:
                return 'danger';
            case self::PRIORITY_HIGH:
                return 'warning';
            case self::PRIORITY_MEDIUM:
                return 'info';
            case self::PRIORITY_LOW:
            default:
                return 'secondary';
        }
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor()
    {
        switch ($this->status) {
            case self::STATUS_APPROVED:
                return 'success';
            case self::STATUS_PENDING:
                return 'warning';
            case self::STATUS_REJECTED:
                return 'danger';
            case self::STATUS_CANCELLED:
                return 'secondary';
            default:
                return 'secondary';
        }
    }

    /**
     * Auto-determine priority based on absence details
     */
    public function determinePriority()
    {
        // Emergency or bereavement = urgent
        if (in_array($this->reason_category, ['emergency', 'family_emergency', 'bereavement'])) {
            return self::PRIORITY_URGENT;
        }

        // Multi-day absences = high priority
        if ($this->isMultiDay()) {
            return self::PRIORITY_HIGH;
        }

        // Same day reporting = high priority
        if ($this->absence_date->isToday()) {
            return self::PRIORITY_HIGH;
        }

        // Multiple periods affected = medium priority
        if (!empty($this->periods_affected) && count($this->periods_affected) > 3) {
            return self::PRIORITY_MEDIUM;
        }

        return self::PRIORITY_LOW;
    }

    /**
     * Send notification about absence
     */
    public function sendNotification()
    {
        // Implementation for sending notifications
        // This could integrate with email, SMS, or push notification services
        $this->notification_sent = true;
        $this->save();
    }

    /**
     * Approve absence
     */
    public function approve($approvedBy)
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approvedBy;
        $this->approved_at = Carbon::now();
        
        // Auto-determine priority if not set
        if (!$this->priority) {
            $this->priority = $this->determinePriority();
        }
        
        $this->save();
        
        // Send notification
        $this->sendNotification();
        
        return $this;
    }

    /**
     * Reject absence
     */
    public function reject($rejectedBy, $reason = null)
    {
        $this->status = self::STATUS_REJECTED;
        $this->approved_by = $rejectedBy;
        $this->approved_at = Carbon::now();
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Rejection reason: " . $reason;
        }
        
        $this->save();
        
        return $this;
    }

    /**
     * Cancel absence
     */
    public function cancel($cancelledBy, $reason = null)
    {
        $this->status = self::STATUS_CANCELLED;
        
        if ($reason) {
            $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Cancellation reason: " . $reason;
        }
        
        $this->save();
        
        // Cancel any associated substitutions
        $this->substitutions()->where('status', '!=', 'completed')->update(['status' => 'cancelled']);
        
        return $this;
    }

    /**
     * Get absence statistics
     */
    public static function getStatistics($startDate = null, $endDate = null)
    {
        $query = self::query();
        
        if ($startDate) {
            $query->whereDate('absence_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('absence_date', '<=', $endDate);
        }
        
        $total = $query->count();
        $approved = $query->where('status', self::STATUS_APPROVED)->count();
        $pending = $query->where('status', self::STATUS_PENDING)->count();
        $rejected = $query->where('status', self::STATUS_REJECTED)->count();
        
        $byCategory = $query->groupBy('reason_category')
            ->selectRaw('reason_category, count(*) as count')
            ->pluck('count', 'reason_category')
            ->toArray();
        
        return [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
            'rejected' => $rejected,
            'by_category' => $byCategory,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0
        ];
    }
}