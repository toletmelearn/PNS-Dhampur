<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class MaintenanceSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inventory_item_id',
        'maintenance_type',
        'title',
        'description',
        'scheduled_date',
        'completed_date',
        'estimated_duration',
        'actual_duration',
        'frequency',
        'frequency_interval',
        'next_due_date',
        'priority',
        'status',
        'assigned_to',
        'completed_by',
        'estimated_cost',
        'actual_cost',
        'vendor_name',
        'work_performed',
        'parts_replaced',
        'notes',
        'requires_downtime',
        'reminder_sent_at'
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'completed_date' => 'datetime',
        'next_due_date' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'frequency_interval' => 'integer',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'requires_downtime' => 'boolean'
    ];

    // Relationships
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOverdue($query)
    {
        return $query->where('scheduled_date', '<', now())
                    ->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('scheduled_date', now())
                    ->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeDueTomorrow($query)
    {
        return $query->whereDate('scheduled_date', now()->addDay())
                    ->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('scheduled_date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeDueThisMonth($query)
    {
        return $query->whereBetween('scheduled_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeCritical($query)
    {
        return $query->where('priority', 'critical');
    }

    public function scopeByMaintenanceType($query, $type)
    {
        return $query->where('maintenance_type', $type);
    }

    public function scopePreventive($query)
    {
        return $query->where('maintenance_type', 'preventive');
    }

    public function scopeCorrective($query)
    {
        return $query->where('maintenance_type', 'corrective');
    }

    public function scopeEmergency($query)
    {
        return $query->where('maintenance_type', 'emergency');
    }

    public function scopeByInventoryItem($query, $inventoryItemId)
    {
        return $query->where('inventory_item_id', $inventoryItemId);
    }

    public function scopeByAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeRecurring($query)
    {
        return $query->whereNotNull('frequency');
    }

    public function scopeOneTime($query)
    {
        return $query->whereNull('frequency');
    }

    public function scopeRequiresDowntime($query)
    {
        return $query->where('requires_downtime', true);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_date', [$startDate, $endDate]);
    }

    public function scopeWithVendor($query)
    {
        return $query->whereNotNull('vendor_name');
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'secondary',
            'overdue' => 'danger'
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getPriorityBadgeAttribute()
    {
        $badges = [
            'low' => 'secondary',
            'medium' => 'primary',
            'high' => 'warning',
            'critical' => 'danger'
        ];

        return $badges[$this->priority] ?? 'secondary';
    }

    public function getIsOverdueAttribute()
    {
        return in_array($this->status, ['pending', 'in_progress']) && 
               $this->scheduled_date && 
               $this->scheduled_date->isPast();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return now()->diffInDays($this->scheduled_date);
    }

    public function getDaysUntilDueAttribute()
    {
        if (!in_array($this->status, ['pending', 'in_progress']) || !$this->scheduled_date) {
            return null;
        }
        
        $days = now()->diffInDays($this->scheduled_date, false);
        return $days > 0 ? $days : 0;
    }

    public function getMaintenanceDurationAttribute()
    {
        if (!$this->completed_date || !$this->scheduled_date) {
            return null;
        }
        
        return $this->scheduled_date->diffInMinutes($this->completed_date);
    }

    public function getEstimatedVsActualDurationAttribute()
    {
        if (!$this->estimated_duration || !$this->actual_duration) {
            return null;
        }
        
        return [
            'estimated' => $this->estimated_duration,
            'actual' => $this->actual_duration,
            'variance' => $this->actual_duration - $this->estimated_duration,
            'variance_percentage' => round((($this->actual_duration - $this->estimated_duration) / $this->estimated_duration) * 100, 2)
        ];
    }

    public function getEstimatedVsActualCostAttribute()
    {
        if (!$this->estimated_cost || !$this->actual_cost) {
            return null;
        }
        
        return [
            'estimated' => $this->estimated_cost,
            'actual' => $this->actual_cost,
            'variance' => $this->actual_cost - $this->estimated_cost,
            'variance_percentage' => round((($this->actual_cost - $this->estimated_cost) / $this->estimated_cost) * 100, 2)
        ];
    }

    public function getCompletionStatusAttribute()
    {
        if ($this->status === 'completed') {
            return 'completed';
        }

        if ($this->is_overdue) {
            return 'overdue';
        }

        if ($this->scheduled_date && $this->scheduled_date->isToday()) {
            return 'due_today';
        }

        if ($this->scheduled_date && $this->scheduled_date->isTomorrow()) {
            return 'due_tomorrow';
        }

        return $this->status;
    }

    public function getNextMaintenanceDateAttribute()
    {
        if (!$this->frequency || !$this->frequency_interval) {
            return null;
        }

        $baseDate = $this->completed_date ?: $this->scheduled_date;
        if (!$baseDate) {
            return null;
        }

        switch ($this->frequency) {
            case 'daily':
                return $baseDate->addDays($this->frequency_interval);
            case 'weekly':
                return $baseDate->addWeeks($this->frequency_interval);
            case 'monthly':
                return $baseDate->addMonths($this->frequency_interval);
            case 'quarterly':
                return $baseDate->addMonths($this->frequency_interval * 3);
            case 'yearly':
                return $baseDate->addYears($this->frequency_interval);
            case 'hours':
                return $baseDate->addHours($this->frequency_interval);
            default:
                return null;
        }
    }

    public function getEfficiencyScoreAttribute()
    {
        if ($this->status !== 'completed') {
            return null;
        }

        $score = 100;

        // Deduct points for being overdue
        if ($this->completed_date && $this->scheduled_date && $this->completed_date->gt($this->scheduled_date)) {
            $daysLate = $this->scheduled_date->diffInDays($this->completed_date);
            $score -= min($daysLate * 5, 30); // Max 30 points deduction
        }

        // Deduct points for cost overrun
        if ($this->estimated_vs_actual_cost && $this->estimated_vs_actual_cost['variance'] > 0) {
            $costOverrunPercentage = abs($this->estimated_vs_actual_cost['variance_percentage']);
            $score -= min($costOverrunPercentage, 25); // Max 25 points deduction
        }

        // Deduct points for duration overrun
        if ($this->estimated_vs_actual_duration && $this->estimated_vs_actual_duration['variance'] > 0) {
            $durationOverrunPercentage = abs($this->estimated_vs_actual_duration['variance_percentage']);
            $score -= min($durationOverrunPercentage / 2, 15); // Max 15 points deduction
        }

        return max($score, 0);
    }

    // Helper Methods
    public function canBeStarted()
    {
        return $this->status === 'pending';
    }

    public function canBeCompleted()
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function canBeRescheduled()
    {
        return in_array($this->status, ['pending', 'in_progress']);
    }

    public function startMaintenance($startedBy = null)
    {
        if (!$this->canBeStarted()) {
            return false;
        }

        $this->status = 'in_progress';
        $this->assigned_to = $startedBy ?: auth()->id();
        $this->save();

        // Update inventory item status if requires downtime
        if ($this->requires_downtime) {
            $this->inventoryItem->update(['status' => 'maintenance']);
        }

        return true;
    }

    public function completeMaintenance($workPerformed = null, $partsReplaced = null, $actualCost = null, $actualDuration = null, $notes = null, $completedBy = null)
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        $this->status = 'completed';
        $this->completed_date = now();
        $this->completed_by = $completedBy ?: auth()->id();
        
        if ($workPerformed) {
            $this->work_performed = $workPerformed;
        }
        
        if ($partsReplaced) {
            $this->parts_replaced = $partsReplaced;
        }
        
        if ($actualCost !== null) {
            $this->actual_cost = $actualCost;
        }
        
        if ($actualDuration !== null) {
            $this->actual_duration = $actualDuration;
        }
        
        if ($notes) {
            $this->notes = $notes;
        }

        // Calculate next due date for recurring maintenance
        if ($this->frequency && $this->frequency_interval) {
            $this->next_due_date = $this->next_maintenance_date;
        }

        $this->save();

        // Update inventory item status back to available
        if ($this->requires_downtime) {
            $this->inventoryItem->update(['status' => 'available']);
        }

        // Create next recurring maintenance if applicable
        if ($this->frequency && $this->frequency_interval) {
            $this->createNextRecurringMaintenance();
        }

        return true;
    }

    public function cancelMaintenance($reason = null)
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->status = 'cancelled';
        
        if ($reason) {
            $this->notes = $this->notes ? $this->notes . "\nCancellation reason: " . $reason : "Cancellation reason: " . $reason;
        }

        $this->save();

        // Update inventory item status back to available if it was in maintenance
        if ($this->requires_downtime && $this->inventoryItem->status === 'maintenance') {
            $this->inventoryItem->update(['status' => 'available']);
        }

        return true;
    }

    public function rescheduleMaintenance($newDate, $reason = null)
    {
        if (!$this->canBeRescheduled()) {
            return false;
        }

        $oldDate = $this->scheduled_date;
        $this->scheduled_date = $newDate;
        
        if ($reason) {
            $rescheduleNote = "Rescheduled from {$oldDate->format('Y-m-d H:i')} to {$newDate->format('Y-m-d H:i')}. Reason: {$reason}";
            $this->notes = $this->notes ? $this->notes . "\n" . $rescheduleNote : $rescheduleNote;
        }

        $this->save();
        return true;
    }

    public function createNextRecurringMaintenance()
    {
        if (!$this->frequency || !$this->frequency_interval || !$this->next_due_date) {
            return null;
        }

        $nextMaintenance = $this->replicate();
        $nextMaintenance->scheduled_date = $this->next_due_date;
        $nextMaintenance->status = 'pending';
        $nextMaintenance->completed_date = null;
        $nextMaintenance->completed_by = null;
        $nextMaintenance->actual_duration = null;
        $nextMaintenance->actual_cost = null;
        $nextMaintenance->work_performed = null;
        $nextMaintenance->parts_replaced = null;
        $nextMaintenance->reminder_sent_at = null;
        $nextMaintenance->next_due_date = null;
        $nextMaintenance->save();

        return $nextMaintenance;
    }

    public function sendReminder()
    {
        // This would integrate with your notification system
        $this->reminder_sent_at = now();
        $this->save();
        
        // You can implement actual notification logic here
        // For example, sending email, SMS, or in-app notifications
        
        return true;
    }

    public function getMaintenanceHistory()
    {
        return static::where('inventory_item_id', $this->inventory_item_id)
                    ->where('id', '!=', $this->id)
                    ->orderBy('scheduled_date', 'desc')
                    ->get();
    }

    public function getMaintenanceReport()
    {
        return [
            'maintenance_id' => $this->id,
            'item_name' => $this->inventoryItem->name,
            'maintenance_type' => $this->maintenance_type,
            'title' => $this->title,
            'scheduled_date' => $this->scheduled_date,
            'completed_date' => $this->completed_date,
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_to' => $this->assignedTo->name ?? null,
            'completed_by' => $this->completedBy->name ?? null,
            'estimated_cost' => $this->estimated_cost,
            'actual_cost' => $this->actual_cost,
            'cost_variance' => $this->estimated_vs_actual_cost,
            'estimated_duration' => $this->estimated_duration,
            'actual_duration' => $this->actual_duration,
            'duration_variance' => $this->estimated_vs_actual_duration,
            'efficiency_score' => $this->efficiency_score,
            'work_performed' => $this->work_performed,
            'parts_replaced' => $this->parts_replaced,
            'vendor_name' => $this->vendor_name,
            'requires_downtime' => $this->requires_downtime,
            'is_recurring' => !is_null($this->frequency),
            'next_due_date' => $this->next_due_date
        ];
    }

    public function duplicate($newScheduledDate = null)
    {
        $newMaintenance = $this->replicate();
        $newMaintenance->scheduled_date = $newScheduledDate ?: now()->addWeek();
        $newMaintenance->status = 'pending';
        $newMaintenance->completed_date = null;
        $newMaintenance->completed_by = null;
        $newMaintenance->actual_duration = null;
        $newMaintenance->actual_cost = null;
        $newMaintenance->work_performed = null;
        $newMaintenance->parts_replaced = null;
        $newMaintenance->reminder_sent_at = null;
        $newMaintenance->next_due_date = null;
        $newMaintenance->save();

        return $newMaintenance;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($maintenance) {
            // Set default values
            if (is_null($maintenance->status)) {
                $maintenance->status = 'pending';
            }
            if (is_null($maintenance->priority)) {
                $maintenance->priority = 'medium';
            }
            if (is_null($maintenance->maintenance_type)) {
                $maintenance->maintenance_type = 'preventive';
            }
            if (is_null($maintenance->scheduled_date)) {
                $maintenance->scheduled_date = now()->addWeek();
            }
            if (is_null($maintenance->requires_downtime)) {
                $maintenance->requires_downtime = false;
            }
        });

        static::updated(function ($maintenance) {
            // Update overdue status
            if ($maintenance->is_overdue && in_array($maintenance->status, ['pending', 'in_progress'])) {
                // You can add logic here to automatically mark as overdue or send notifications
            }
        });
    }
}
