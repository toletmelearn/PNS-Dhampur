<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'vendor_id',
        'requested_by',
        'approved_by',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'priority',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'delivery_address',
        'terms_and_conditions',
        'notes',
        'rejection_reason',
        'approved_at',
        'sent_at',
        'completed_at'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function pendingItems()
    {
        return $this->hasMany(PurchaseOrderItem::class)->where('status', 'pending');
    }

    public function receivedItems()
    {
        return $this->hasMany(PurchaseOrderItem::class)->where('status', 'received');
    }

    public function partiallyReceivedItems()
    {
        return $this->hasMany(PurchaseOrderItem::class)->where('status', 'partially_received');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeOverdue($query)
    {
        return $query->where('expected_delivery_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled', 'rejected']);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('order_date', [$startDate, $endDate]);
    }

    public function scopeByAmountRange($query, $minAmount, $maxAmount)
    {
        return $query->whereBetween('total_amount', [$minAmount, $maxAmount]);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'approved' => 'info',
            'sent' => 'primary',
            'partially_received' => 'secondary',
            'received' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger',
            'rejected' => 'danger'
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getPriorityBadgeAttribute()
    {
        $badges = [
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            'urgent' => 'danger'
        ];

        return $badges[$this->priority] ?? 'secondary';
    }

    public function getIsOverdueAttribute()
    {
        return $this->expected_delivery_date && 
               $this->expected_delivery_date->isPast() && 
               !in_array($this->status, ['completed', 'cancelled', 'rejected']);
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return now()->diffInDays($this->expected_delivery_date);
    }

    public function getDeliveryStatusAttribute()
    {
        if (in_array($this->status, ['completed', 'cancelled', 'rejected'])) {
            return $this->status;
        }

        if ($this->is_overdue) {
            return 'overdue';
        }

        if ($this->expected_delivery_date && $this->expected_delivery_date->isToday()) {
            return 'due_today';
        }

        if ($this->expected_delivery_date && $this->expected_delivery_date->isTomorrow()) {
            return 'due_tomorrow';
        }

        return 'on_track';
    }

    public function getCompletionPercentageAttribute()
    {
        $totalItems = $this->items()->count();
        if ($totalItems == 0) {
            return 0;
        }

        $completedItems = $this->items()->where('status', 'received')->count();
        return round(($completedItems / $totalItems) * 100, 2);
    }

    public function getTotalReceivedAmountAttribute()
    {
        return $this->items()->sum('total_price');
    }

    public function getPendingAmountAttribute()
    {
        return $this->total_amount - $this->total_received_amount;
    }

    // Helper Methods
    public function canBeApproved()
    {
        return $this->status === 'pending';
    }

    public function canBeRejected()
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    public function canBeCancelled()
    {
        return !in_array($this->status, ['completed', 'cancelled', 'rejected']);
    }

    public function canBeSent()
    {
        return $this->status === 'approved';
    }

    public function canReceiveItems()
    {
        return in_array($this->status, ['sent', 'partially_received']);
    }

    public function approve($approvedBy = null)
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->status = 'approved';
        $this->approved_by = $approvedBy ?: auth()->id();
        $this->approved_at = now();
        $this->save();

        return true;
    }

    public function reject($reason = null, $rejectedBy = null)
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->approved_by = $rejectedBy ?: auth()->id();
        $this->approved_at = now();
        $this->save();

        return true;
    }

    public function send()
    {
        if (!$this->canBeSent()) {
            return false;
        }

        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();

        // Update vendor statistics
        $this->vendor->addPurchaseOrder($this->total_amount);

        return true;
    }

    public function cancel($reason = null)
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->status = 'cancelled';
        $this->notes = $reason ? "Cancelled: {$reason}" : 'Cancelled';
        $this->save();

        return true;
    }

    public function markAsReceived()
    {
        $this->status = 'received';
        $this->actual_delivery_date = now();
        $this->save();

        // Update all items as received
        $this->items()->update([
            'status' => 'received',
            'quantity_received' => \DB::raw('quantity_ordered'),
            'quantity_pending' => 0,
            'received_date' => now()
        ]);

        $this->updateInventoryStock();
        $this->checkCompletion();

        return true;
    }

    public function receiveItem($itemId, $quantityReceived, $notes = null)
    {
        $item = $this->items()->find($itemId);
        if (!$item) {
            return false;
        }

        $item->receiveQuantity($quantityReceived, $notes);
        $this->updateStatus();
        $this->updateInventoryStock();

        return true;
    }

    public function updateStatus()
    {
        $totalItems = $this->items()->count();
        $receivedItems = $this->items()->where('status', 'received')->count();
        $partiallyReceivedItems = $this->items()->where('status', 'partially_received')->count();

        if ($receivedItems == $totalItems) {
            $this->status = 'received';
            $this->actual_delivery_date = now();
        } elseif ($receivedItems > 0 || $partiallyReceivedItems > 0) {
            $this->status = 'partially_received';
        }

        $this->save();
        $this->checkCompletion();
    }

    public function checkCompletion()
    {
        if ($this->status === 'received') {
            $this->status = 'completed';
            $this->completed_at = now();
            $this->save();
        }
    }

    public function updateInventoryStock()
    {
        foreach ($this->items as $item) {
            if ($item->quantity_received > 0) {
                $inventoryItem = $item->inventoryItem;
                if ($inventoryItem) {
                    $inventoryItem->addStock(
                        $item->quantity_received,
                        "Received from PO: {$this->po_number}"
                    );
                }
            }
        }
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->items()->sum('total_price');
        
        // Calculate tax (assuming 18% GST)
        $this->tax_amount = $this->subtotal * 0.18;
        
        // Apply discount if any
        $discountedAmount = $this->subtotal - $this->discount_amount;
        
        // Add tax and shipping
        $this->total_amount = $discountedAmount + $this->tax_amount + $this->shipping_cost;
        
        $this->save();
    }

    public function addItem($inventoryItemId, $quantity, $unitPrice, $specifications = null)
    {
        $item = $this->items()->create([
            'inventory_item_id' => $inventoryItemId,
            'quantity_ordered' => $quantity,
            'quantity_pending' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
            'specifications' => $specifications,
            'status' => 'pending'
        ]);

        $this->calculateTotals();
        return $item;
    }

    public function removeItem($itemId)
    {
        $item = $this->items()->find($itemId);
        if ($item) {
            $item->delete();
            $this->calculateTotals();
            return true;
        }
        return false;
    }

    public function updateItem($itemId, $quantity = null, $unitPrice = null, $specifications = null)
    {
        $item = $this->items()->find($itemId);
        if (!$item) {
            return false;
        }

        if ($quantity !== null) {
            $item->quantity_ordered = $quantity;
            $item->quantity_pending = $quantity - $item->quantity_received;
        }

        if ($unitPrice !== null) {
            $item->unit_price = $unitPrice;
        }

        if ($specifications !== null) {
            $item->specifications = $specifications;
        }

        $item->total_price = $item->quantity_ordered * $item->unit_price;
        $item->save();

        $this->calculateTotals();
        return true;
    }

    public function duplicate()
    {
        $newPO = $this->replicate();
        $newPO->po_number = static::generatePONumber();
        $newPO->status = 'pending';
        $newPO->order_date = now();
        $newPO->approved_by = null;
        $newPO->approved_at = null;
        $newPO->sent_at = null;
        $newPO->completed_at = null;
        $newPO->actual_delivery_date = null;
        $newPO->save();

        // Duplicate items
        foreach ($this->items as $item) {
            $newItem = $item->replicate();
            $newItem->purchase_order_id = $newPO->id;
            $newItem->quantity_received = 0;
            $newItem->quantity_pending = $newItem->quantity_ordered;
            $newItem->status = 'pending';
            $newItem->received_date = null;
            $newItem->save();
        }

        return $newPO;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($po) {
            if (empty($po->po_number)) {
                $po->po_number = static::generatePONumber();
            }
            
            // Set default values
            if (is_null($po->status)) {
                $po->status = 'pending';
            }
            if (is_null($po->priority)) {
                $po->priority = 'medium';
            }
            if (is_null($po->order_date)) {
                $po->order_date = now();
            }
            if (is_null($po->requested_by)) {
                $po->requested_by = auth()->id();
            }
        });
    }

    public static function generatePONumber()
    {
        $prefix = 'PO' . date('Y');
        
        $lastPO = static::where('po_number', 'like', $prefix . '%')
                        ->orderBy('po_number', 'desc')
                        ->first();

        if ($lastPO) {
            $lastNumber = (int) substr($lastPO->po_number, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
