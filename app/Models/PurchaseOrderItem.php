<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'quantity_ordered',
        'quantity_received',
        'quantity_pending',
        'unit_price',
        'total_price',
        'specifications',
        'notes',
        'status',
        'expected_date',
        'received_date'
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'quantity_pending' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'expected_date' => 'date',
        'received_date' => 'datetime'
    ];

    // Relationships
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePartiallyReceived($query)
    {
        return $query->where('status', 'partially_received');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOverdue($query)
    {
        return $query->where('expected_date', '<', now())
                    ->whereNotIn('status', ['received', 'cancelled']);
    }

    public function scopeByInventoryItem($query, $inventoryItemId)
    {
        return $query->where('inventory_item_id', $inventoryItemId);
    }

    public function scopeByPurchaseOrder($query, $purchaseOrderId)
    {
        return $query->where('purchase_order_id', $purchaseOrderId);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'partially_received' => 'info',
            'received' => 'success',
            'cancelled' => 'danger'
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    public function getReceiptPercentageAttribute()
    {
        if ($this->quantity_ordered == 0) {
            return 0;
        }
        return round(($this->quantity_received / $this->quantity_ordered) * 100, 2);
    }

    public function getIsOverdueAttribute()
    {
        return $this->expected_date && 
               $this->expected_date->isPast() && 
               !in_array($this->status, ['received', 'cancelled']);
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return now()->diffInDays($this->expected_date);
    }

    public function getIsFullyReceivedAttribute()
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    public function getIsPartiallyReceivedAttribute()
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity_ordered;
    }

    public function getRemainingQuantityAttribute()
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    public function getReceivedValueAttribute()
    {
        return $this->quantity_received * $this->unit_price;
    }

    public function getPendingValueAttribute()
    {
        return $this->remaining_quantity * $this->unit_price;
    }

    // Helper Methods
    public function canReceive($quantity = null)
    {
        if (in_array($this->status, ['received', 'cancelled'])) {
            return false;
        }

        if ($quantity !== null) {
            return ($this->quantity_received + $quantity) <= $this->quantity_ordered;
        }

        return $this->quantity_received < $this->quantity_ordered;
    }

    public function receiveQuantity($quantity, $notes = null)
    {
        if (!$this->canReceive($quantity)) {
            return false;
        }

        $this->quantity_received += $quantity;
        $this->quantity_pending = max(0, $this->quantity_ordered - $this->quantity_received);
        
        if ($notes) {
            $this->notes = $notes;
        }

        // Update status based on received quantity
        if ($this->quantity_received >= $this->quantity_ordered) {
            $this->status = 'received';
            $this->received_date = now();
        } elseif ($this->quantity_received > 0) {
            $this->status = 'partially_received';
            if (!$this->received_date) {
                $this->received_date = now();
            }
        }

        $this->save();

        // Update inventory stock
        if ($quantity > 0) {
            $this->inventoryItem->addStock(
                $quantity,
                "Received from PO: {$this->purchaseOrder->po_number}"
            );
        }

        // Update purchase order status
        $this->purchaseOrder->updateStatus();

        return true;
    }

    public function receiveAll($notes = null)
    {
        $remainingQuantity = $this->remaining_quantity;
        if ($remainingQuantity > 0) {
            return $this->receiveQuantity($remainingQuantity, $notes);
        }
        return false;
    }

    public function cancel($reason = null)
    {
        if ($this->status === 'received') {
            return false;
        }

        $this->status = 'cancelled';
        $this->quantity_pending = 0;
        
        if ($reason) {
            $this->notes = $reason;
        }

        $this->save();

        // Update purchase order status
        $this->purchaseOrder->updateStatus();

        return true;
    }

    public function updateQuantity($newQuantity)
    {
        if ($this->quantity_received > $newQuantity) {
            return false; // Cannot reduce below received quantity
        }

        $this->quantity_ordered = $newQuantity;
        $this->quantity_pending = $newQuantity - $this->quantity_received;
        $this->total_price = $newQuantity * $this->unit_price;

        // Update status if necessary
        if ($this->quantity_received >= $this->quantity_ordered) {
            $this->status = 'received';
        } elseif ($this->quantity_received > 0) {
            $this->status = 'partially_received';
        } else {
            $this->status = 'pending';
        }

        $this->save();

        // Recalculate purchase order totals
        $this->purchaseOrder->calculateTotals();

        return true;
    }

    public function updatePrice($newUnitPrice)
    {
        $this->unit_price = $newUnitPrice;
        $this->total_price = $this->quantity_ordered * $newUnitPrice;
        $this->save();

        // Recalculate purchase order totals
        $this->purchaseOrder->calculateTotals();

        return true;
    }

    public function getReceiptHistory()
    {
        // This would typically come from a separate receipt_logs table
        // For now, we'll return basic information
        return [
            'total_ordered' => $this->quantity_ordered,
            'total_received' => $this->quantity_received,
            'total_pending' => $this->quantity_pending,
            'receipt_percentage' => $this->receipt_percentage,
            'last_received_date' => $this->received_date,
            'status' => $this->status
        ];
    }

    public function getExpectedDeliveryStatus()
    {
        if ($this->status === 'received') {
            return 'completed';
        }

        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if (!$this->expected_date) {
            return 'no_date_set';
        }

        if ($this->expected_date->isPast()) {
            return 'overdue';
        }

        if ($this->expected_date->isToday()) {
            return 'due_today';
        }

        if ($this->expected_date->isTomorrow()) {
            return 'due_tomorrow';
        }

        return 'on_schedule';
    }

    public function duplicate($newPurchaseOrderId)
    {
        $newItem = $this->replicate();
        $newItem->purchase_order_id = $newPurchaseOrderId;
        $newItem->quantity_received = 0;
        $newItem->quantity_pending = $newItem->quantity_ordered;
        $newItem->status = 'pending';
        $newItem->received_date = null;
        $newItem->save();

        return $newItem;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Set default values
            if (is_null($item->status)) {
                $item->status = 'pending';
            }
            if (is_null($item->quantity_received)) {
                $item->quantity_received = 0;
            }
            if (is_null($item->quantity_pending)) {
                $item->quantity_pending = $item->quantity_ordered;
            }
            if (is_null($item->total_price)) {
                $item->total_price = $item->quantity_ordered * $item->unit_price;
            }
        });

        static::updating(function ($item) {
            // Recalculate total price if quantity or unit price changes
            if ($item->isDirty(['quantity_ordered', 'unit_price'])) {
                $item->total_price = $item->quantity_ordered * $item->unit_price;
            }
        });
    }
}
