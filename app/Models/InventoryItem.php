<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'item_code',
        'barcode',
        'category_id',
        'unit_of_measurement',
        'unit_price',
        'current_stock',
        'minimum_stock_level',
        'maximum_stock_level',
        'reorder_point',
        'reorder_quantity',
        'location',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'warranty_expiry',
        'condition',
        'status',
        'is_asset',
        'depreciation_rate',
        'notes'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'current_stock' => 'integer',
        'minimum_stock_level' => 'integer',
        'maximum_stock_level' => 'integer',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'purchase_price' => 'decimal:2',
        'purchase_date' => 'date',
        'warranty_expiry' => 'date',
        'is_asset' => 'boolean',
        'depreciation_rate' => 'decimal:2'
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function assetAllocations()
    {
        return $this->hasMany(AssetAllocation::class);
    }

    public function maintenanceSchedules()
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function currentAllocation()
    {
        return $this->hasOne(AssetAllocation::class)->where('status', 'allocated');
    }

    public function upcomingMaintenance()
    {
        return $this->hasMany(MaintenanceSchedule::class)
                    ->where('status', 'scheduled')
                    ->where('scheduled_date', '>=', now())
                    ->orderBy('scheduled_date');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where('current_stock', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'minimum_stock_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', 0);
    }

    public function scopeNeedsReorder($query)
    {
        return $query->whereColumn('current_stock', '<=', 'reorder_point');
    }

    public function scopeAssets($query)
    {
        return $query->where('is_asset', true);
    }

    public function scopeConsumables($query)
    {
        return $query->where('is_asset', false);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeWarrantyExpiring($query, $days = 30)
    {
        return $query->where('warranty_expiry', '<=', now()->addDays($days))
                    ->where('warranty_expiry', '>=', now());
    }

    // Accessors
    public function getStockStatusAttribute()
    {
        if ($this->current_stock == 0) {
            return 'out_of_stock';
        } elseif ($this->current_stock <= $this->minimum_stock_level) {
            return 'low_stock';
        } elseif ($this->current_stock >= $this->maximum_stock_level) {
            return 'overstock';
        }
        return 'in_stock';
    }

    public function getStockPercentageAttribute()
    {
        if ($this->maximum_stock_level == 0) {
            return 0;
        }
        return round(($this->current_stock / $this->maximum_stock_level) * 100, 2);
    }

    public function getCurrentValueAttribute()
    {
        return $this->current_stock * $this->unit_price;
    }

    public function getDepreciatedValueAttribute()
    {
        if (!$this->is_asset || !$this->purchase_date || !$this->depreciation_rate) {
            return $this->current_value;
        }

        $yearsOld = $this->purchase_date->diffInYears(now());
        $depreciationAmount = $this->purchase_price * ($this->depreciation_rate / 100) * $yearsOld;
        
        return max(0, $this->purchase_price - $depreciationAmount);
    }

    public function getIsWarrantyValidAttribute()
    {
        return $this->warranty_expiry && $this->warranty_expiry->isFuture();
    }

    public function getWarrantyDaysRemainingAttribute()
    {
        if (!$this->warranty_expiry || $this->warranty_expiry->isPast()) {
            return 0;
        }
        return now()->diffInDays($this->warranty_expiry);
    }

    // Helper Methods
    public function isLowStock()
    {
        return $this->current_stock <= $this->minimum_stock_level;
    }

    public function needsReorder()
    {
        return $this->current_stock <= $this->reorder_point;
    }

    public function isOutOfStock()
    {
        return $this->current_stock == 0;
    }

    public function isOverstock()
    {
        return $this->current_stock >= $this->maximum_stock_level;
    }

    public function adjustStock($quantity, $type = 'adjustment', $notes = null)
    {
        $oldStock = $this->current_stock;
        $this->current_stock += $quantity;
        
        if ($this->current_stock < 0) {
            $this->current_stock = 0;
        }
        
        $this->save();

        // Log the stock movement (you can create a StockMovement model for this)
        // StockMovement::create([
        //     'inventory_item_id' => $this->id,
        //     'type' => $type,
        //     'quantity' => $quantity,
        //     'old_stock' => $oldStock,
        //     'new_stock' => $this->current_stock,
        //     'notes' => $notes,
        //     'created_by' => auth()->id()
        // ]);

        return $this;
    }

    public function addStock($quantity, $notes = null)
    {
        return $this->adjustStock($quantity, 'addition', $notes);
    }

    public function removeStock($quantity, $notes = null)
    {
        return $this->adjustStock(-$quantity, 'removal', $notes);
    }

    public function setStock($quantity, $notes = null)
    {
        $adjustment = $quantity - $this->current_stock;
        return $this->adjustStock($adjustment, 'set', $notes);
    }

    public function isAllocated()
    {
        return $this->currentAllocation()->exists();
    }

    public function getAllocatedTo()
    {
        $allocation = $this->currentAllocation;
        if (!$allocation) {
            return null;
        }

        return [
            'type' => $allocation->allocated_to_type,
            'id' => $allocation->allocated_to_id,
            'name' => $allocation->allocated_to_name,
            'date' => $allocation->allocation_date,
            'purpose' => $allocation->allocation_purpose
        ];
    }

    public function getNextMaintenanceDate()
    {
        $nextMaintenance = $this->upcomingMaintenance()->first();
        return $nextMaintenance ? $nextMaintenance->scheduled_date : null;
    }

    public function isDueMaintenance($days = 7)
    {
        $nextDate = $this->getNextMaintenanceDate();
        return $nextDate && $nextDate->lte(now()->addDays($days));
    }

    public function calculateReorderSuggestion()
    {
        if (!$this->needsReorder()) {
            return null;
        }

        // Calculate average consumption over last 30 days
        // This would require a stock movement history
        $suggestedQuantity = $this->reorder_quantity ?: ($this->maximum_stock_level - $this->current_stock);

        return [
            'current_stock' => $this->current_stock,
            'reorder_point' => $this->reorder_point,
            'suggested_quantity' => $suggestedQuantity,
            'estimated_cost' => $suggestedQuantity * $this->unit_price,
            'priority' => $this->isOutOfStock() ? 'urgent' : 'normal'
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->item_code)) {
                $item->item_code = static::generateItemCode($item->category_id);
            }
        });
    }

    public static function generateItemCode($categoryId = null)
    {
        $prefix = 'ITM';
        
        if ($categoryId) {
            $category = InventoryCategory::find($categoryId);
            if ($category && $category->code) {
                $prefix = $category->code;
            }
        }

        $lastItem = static::where('item_code', 'like', $prefix . '%')
                          ->orderBy('item_code', 'desc')
                          ->first();

        if ($lastItem) {
            $lastNumber = (int) substr($lastItem->item_code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
