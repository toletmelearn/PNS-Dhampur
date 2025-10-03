<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'inventory_item_id',
        'allocated_to_type',
        'allocated_to_id',
        'allocated_to_name',
        'allocated_by',
        'allocation_date',
        'expected_return_date',
        'actual_return_date',
        'status',
        'allocation_purpose',
        'condition_at_allocation',
        'condition_at_return',
        'usage_notes',
        'usage_hours',
        'damage_cost',
        'return_notes',
        'returned_by'
    ];

    protected $casts = [
        'allocation_date' => 'date',
        'expected_return_date' => 'date',
        'actual_return_date' => 'datetime',
        'usage_hours' => 'decimal:2',
        'damage_cost' => 'decimal:2'
    ];

    // Relationships
    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function allocatedBy()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    // Polymorphic relationship for allocated_to
    public function allocatedTo()
    {
        return $this->morphTo('allocated_to', 'allocated_to_type', 'allocated_to_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'allocated');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeOverdue($query)
    {
        return $query->where('expected_return_date', '<', now())
                    ->where('status', 'allocated');
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('expected_return_date', now())
                    ->where('status', 'allocated');
    }

    public function scopeDueTomorrow($query)
    {
        return $query->whereDate('expected_return_date', now()->addDay())
                    ->where('status', 'allocated');
    }

    public function scopeByAllocatedToType($query, $type)
    {
        return $query->where('allocated_to_type', $type);
    }

    public function scopeByInventoryItem($query, $inventoryItemId)
    {
        return $query->where('inventory_item_id', $inventoryItemId);
    }

    public function scopeByAllocatedBy($query, $userId)
    {
        return $query->where('allocated_by', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('allocation_date', [$startDate, $endDate]);
    }

    public function scopeByCondition($query, $condition)
    {
        return $query->where('condition_at_allocation', $condition);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'allocated' => 'primary',
            'returned' => 'success',
            'overdue' => 'danger',
            'damaged' => 'warning',
            'lost' => 'danger'
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === 'allocated' && 
               $this->expected_return_date && 
               $this->expected_return_date->isPast();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return now()->diffInDays($this->expected_return_date);
    }

    public function getDaysUntilDueAttribute()
    {
        if ($this->status !== 'allocated' || !$this->expected_return_date) {
            return null;
        }
        
        $days = now()->diffInDays($this->expected_return_date, false);
        return $days > 0 ? $days : 0;
    }

    public function getAllocationDurationAttribute()
    {
        $endDate = $this->actual_return_date ?: now();
        return $this->allocation_date->diffInDays($endDate);
    }

    public function getReturnStatusAttribute()
    {
        if ($this->status === 'returned') {
            return 'returned';
        }

        if ($this->is_overdue) {
            return 'overdue';
        }

        if ($this->expected_return_date && $this->expected_return_date->isToday()) {
            return 'due_today';
        }

        if ($this->expected_return_date && $this->expected_return_date->isTomorrow()) {
            return 'due_tomorrow';
        }

        return 'active';
    }

    public function getConditionChangeAttribute()
    {
        if (!$this->condition_at_return || !$this->condition_at_allocation) {
            return null;
        }

        $conditions = ['excellent' => 5, 'good' => 4, 'fair' => 3, 'poor' => 2, 'damaged' => 1];
        
        $initialScore = $conditions[$this->condition_at_allocation] ?? 0;
        $returnScore = $conditions[$this->condition_at_return] ?? 0;
        
        $change = $returnScore - $initialScore;
        
        if ($change > 0) {
            return 'improved';
        } elseif ($change < 0) {
            return 'deteriorated';
        } else {
            return 'unchanged';
        }
    }

    public function getUsageRateAttribute()
    {
        if (!$this->usage_hours || $this->allocation_duration == 0) {
            return 0;
        }
        
        return round($this->usage_hours / ($this->allocation_duration * 24), 2);
    }

    // Helper Methods
    public function canBeReturned()
    {
        return $this->status === 'allocated';
    }

    public function canBeExtended()
    {
        return $this->status === 'allocated';
    }

    public function returnAsset($condition = null, $notes = null, $usageHours = null, $damageCost = null, $returnedBy = null)
    {
        if (!$this->canBeReturned()) {
            return false;
        }

        $this->status = 'returned';
        $this->actual_return_date = now();
        $this->returned_by = $returnedBy ?: auth()->id();
        
        if ($condition) {
            $this->condition_at_return = $condition;
        }
        
        if ($notes) {
            $this->return_notes = $notes;
        }
        
        if ($usageHours !== null) {
            $this->usage_hours = $usageHours;
        }
        
        if ($damageCost !== null) {
            $this->damage_cost = $damageCost;
            if ($damageCost > 0) {
                $this->status = 'damaged';
            }
        }

        $this->save();

        // Update inventory item status
        $this->inventoryItem->update(['status' => 'available']);

        return true;
    }

    public function extendAllocation($newReturnDate, $reason = null)
    {
        if (!$this->canBeExtended()) {
            return false;
        }

        $oldDate = $this->expected_return_date;
        $this->expected_return_date = $newReturnDate;
        
        if ($reason) {
            $extensionNote = "Extended from {$oldDate->format('Y-m-d')} to {$newReturnDate->format('Y-m-d')}. Reason: {$reason}";
            $this->usage_notes = $this->usage_notes ? $this->usage_notes . "\n" . $extensionNote : $extensionNote;
        }

        $this->save();
        return true;
    }

    public function markAsLost($notes = null)
    {
        if ($this->status !== 'allocated') {
            return false;
        }

        $this->status = 'lost';
        $this->actual_return_date = now();
        
        if ($notes) {
            $this->return_notes = $notes;
        }

        $this->save();

        // Update inventory item status
        $this->inventoryItem->update(['status' => 'lost']);

        return true;
    }

    public function markAsDamaged($damageCost = null, $notes = null)
    {
        if ($this->status !== 'allocated') {
            return false;
        }

        $this->status = 'damaged';
        $this->condition_at_return = 'damaged';
        
        if ($damageCost !== null) {
            $this->damage_cost = $damageCost;
        }
        
        if ($notes) {
            $this->return_notes = $notes;
        }

        $this->save();
        return true;
    }

    public function updateUsage($hours, $notes = null)
    {
        if ($this->status !== 'allocated') {
            return false;
        }

        $this->usage_hours = $hours;
        
        if ($notes) {
            $this->usage_notes = $notes;
        }

        $this->save();
        return true;
    }

    public function getUsageReport()
    {
        return [
            'allocation_id' => $this->id,
            'item_name' => $this->inventoryItem->name,
            'allocated_to' => $this->allocated_to_name,
            'allocation_date' => $this->allocation_date,
            'expected_return_date' => $this->expected_return_date,
            'actual_return_date' => $this->actual_return_date,
            'duration_days' => $this->allocation_duration,
            'usage_hours' => $this->usage_hours,
            'usage_rate' => $this->usage_rate,
            'condition_change' => $this->condition_change,
            'damage_cost' => $this->damage_cost,
            'status' => $this->status,
            'return_status' => $this->return_status
        ];
    }

    public function getAllocationHistory()
    {
        return static::where('inventory_item_id', $this->inventory_item_id)
                    ->where('id', '!=', $this->id)
                    ->orderBy('allocation_date', 'desc')
                    ->get();
    }

    public function duplicate($newAllocationDate = null, $newExpectedReturnDate = null)
    {
        $newAllocation = $this->replicate();
        $newAllocation->allocation_date = $newAllocationDate ?: now();
        $newAllocation->expected_return_date = $newExpectedReturnDate;
        $newAllocation->actual_return_date = null;
        $newAllocation->status = 'allocated';
        $newAllocation->condition_at_return = null;
        $newAllocation->usage_hours = null;
        $newAllocation->damage_cost = null;
        $newAllocation->return_notes = null;
        $newAllocation->returned_by = null;
        $newAllocation->save();

        return $newAllocation;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($allocation) {
            // Set default values
            if (is_null($allocation->status)) {
                $allocation->status = 'allocated';
            }
            if (is_null($allocation->allocation_date)) {
                $allocation->allocation_date = now();
            }
            if (is_null($allocation->allocated_by)) {
                $allocation->allocated_by = auth()->id();
            }
            if (is_null($allocation->condition_at_allocation)) {
                $allocation->condition_at_allocation = 'good';
            }
        });

        static::created(function ($allocation) {
            // Update inventory item status to allocated
            $allocation->inventoryItem->update(['status' => 'allocated']);
        });

        static::updated(function ($allocation) {
            // Update overdue status
            if ($allocation->is_overdue && $allocation->status === 'allocated') {
                $allocation->update(['status' => 'overdue']);
            }
        });
    }
}
