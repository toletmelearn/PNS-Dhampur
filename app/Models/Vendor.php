<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_code',
        'name',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'gst_number',
        'pan_number',
        'bank_name',
        'bank_account_number',
        'bank_ifsc_code',
        'payment_terms',
        'credit_limit',
        'outstanding_balance',
        'status',
        'rating',
        'total_orders',
        'total_purchase_amount',
        'last_order_date',
        'notes'
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'total_purchase_amount' => 'decimal:2',
        'total_orders' => 'integer',
        'rating' => 'integer',
        'last_order_date' => 'date'
    ];

    // Relationships
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function activePurchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class)
                    ->whereIn('status', ['pending', 'approved', 'sent', 'partially_received']);
    }

    public function completedPurchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class)
                    ->where('status', 'completed');
    }

    public function recentPurchaseOrders($days = 30)
    {
        return $this->hasMany(PurchaseOrder::class)
                    ->where('order_date', '>=', now()->subDays($days))
                    ->orderBy('order_date', 'desc');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeBlacklisted($query)
    {
        return $query->where('status', 'blacklisted');
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeHighRated($query, $minRating = 4)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeWithOutstandingBalance($query)
    {
        return $query->where('outstanding_balance', '>', 0);
    }

    public function scopeExceedingCreditLimit($query)
    {
        return $query->whereColumn('outstanding_balance', '>', 'credit_limit');
    }

    public function scopeByLocation($query, $city = null, $state = null)
    {
        if ($city) {
            $query->where('city', 'like', "%{$city}%");
        }
        if ($state) {
            $query->where('state', 'like', "%{$state}%");
        }
        return $query;
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->company_name ?: $this->name;
    }

    public function getFullAddressAttribute()
    {
        $address = [];
        if ($this->address) $address[] = $this->address;
        if ($this->city) $address[] = $this->city;
        if ($this->state) $address[] = $this->state;
        if ($this->postal_code) $address[] = $this->postal_code;
        if ($this->country) $address[] = $this->country;
        
        return implode(', ', $address);
    }

    public function getAvailableCreditAttribute()
    {
        return max(0, $this->credit_limit - $this->outstanding_balance);
    }

    public function getCreditUtilizationPercentageAttribute()
    {
        if ($this->credit_limit == 0) {
            return 0;
        }
        return round(($this->outstanding_balance / $this->credit_limit) * 100, 2);
    }

    public function getAverageOrderValueAttribute()
    {
        if ($this->total_orders == 0) {
            return 0;
        }
        return round($this->total_purchase_amount / $this->total_orders, 2);
    }

    public function getPerformanceScoreAttribute()
    {
        $score = 0;
        
        // Rating contributes 40%
        $score += ($this->rating / 5) * 40;
        
        // On-time delivery contributes 30%
        $onTimeDeliveries = $this->purchaseOrders()
                                 ->where('status', 'completed')
                                 ->whereColumn('actual_delivery_date', '<=', 'expected_delivery_date')
                                 ->count();
        $totalCompletedOrders = $this->purchaseOrders()->where('status', 'completed')->count();
        
        if ($totalCompletedOrders > 0) {
            $onTimePercentage = ($onTimeDeliveries / $totalCompletedOrders) * 100;
            $score += ($onTimePercentage / 100) * 30;
        }
        
        // Payment compliance contributes 20%
        $paymentScore = $this->outstanding_balance <= $this->credit_limit ? 20 : 10;
        $score += $paymentScore;
        
        // Order frequency contributes 10%
        $recentOrders = $this->recentPurchaseOrders(90)->count();
        $frequencyScore = min(10, $recentOrders * 2);
        $score += $frequencyScore;
        
        return round($score, 2);
    }

    public function getRatingStarsAttribute()
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    // Helper Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isBlacklisted()
    {
        return $this->status === 'blacklisted';
    }

    public function hasOutstandingBalance()
    {
        return $this->outstanding_balance > 0;
    }

    public function isExceedingCreditLimit()
    {
        return $this->outstanding_balance > $this->credit_limit;
    }

    public function canPlaceOrder($amount = 0)
    {
        if (!$this->isActive()) {
            return false;
        }
        
        if ($this->isBlacklisted()) {
            return false;
        }
        
        $totalAmount = $this->outstanding_balance + $amount;
        return $totalAmount <= $this->credit_limit;
    }

    public function addPurchaseOrder($amount)
    {
        $this->total_orders += 1;
        $this->total_purchase_amount += $amount;
        $this->outstanding_balance += $amount;
        $this->last_order_date = now();
        $this->save();
        
        return $this;
    }

    public function makePayment($amount, $notes = null)
    {
        $this->outstanding_balance = max(0, $this->outstanding_balance - $amount);
        $this->save();
        
        // Log payment (you can create a VendorPayment model for this)
        // VendorPayment::create([
        //     'vendor_id' => $this->id,
        //     'amount' => $amount,
        //     'payment_date' => now(),
        //     'notes' => $notes,
        //     'created_by' => auth()->id()
        // ]);
        
        return $this;
    }

    public function updateRating($newRating)
    {
        if ($newRating >= 1 && $newRating <= 5) {
            $this->rating = $newRating;
            $this->save();
        }
        
        return $this;
    }

    public function getOrderHistory($limit = 10)
    {
        return $this->purchaseOrders()
                    ->with(['items.inventoryItem'])
                    ->orderBy('order_date', 'desc')
                    ->limit($limit)
                    ->get();
    }

    public function getMonthlyOrderStats($months = 12)
    {
        $stats = [];
        
        for ($i = 0; $i < $months; $i++) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            
            $orders = $this->purchaseOrders()
                           ->whereBetween('order_date', [$monthStart, $monthEnd])
                           ->get();
            
            $stats[] = [
                'month' => $date->format('M Y'),
                'orders_count' => $orders->count(),
                'total_amount' => $orders->sum('total_amount'),
                'average_amount' => $orders->count() > 0 ? $orders->avg('total_amount') : 0
            ];
        }
        
        return array_reverse($stats);
    }

    public function getTopPurchasedItems($limit = 10)
    {
        return PurchaseOrderItem::whereHas('purchaseOrder', function($query) {
                    $query->where('vendor_id', $this->id);
                })
                ->with('inventoryItem')
                ->selectRaw('inventory_item_id, SUM(quantity_ordered) as total_quantity, SUM(total_price) as total_amount')
                ->groupBy('inventory_item_id')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit)
                ->get();
    }

    public function blacklist($reason = null)
    {
        $this->status = 'blacklisted';
        $this->notes = $reason ? "Blacklisted: {$reason}" : 'Blacklisted';
        $this->save();
        
        return $this;
    }

    public function activate()
    {
        $this->status = 'active';
        $this->save();
        
        return $this;
    }

    public function deactivate($reason = null)
    {
        $this->status = 'inactive';
        if ($reason) {
            $this->notes = "Deactivated: {$reason}";
        }
        $this->save();
        
        return $this;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vendor) {
            if (empty($vendor->vendor_code)) {
                $vendor->vendor_code = static::generateVendorCode();
            }
            
            // Set default values
            if (is_null($vendor->status)) {
                $vendor->status = 'active';
            }
            if (is_null($vendor->rating)) {
                $vendor->rating = 3;
            }
            if (is_null($vendor->total_orders)) {
                $vendor->total_orders = 0;
            }
            if (is_null($vendor->total_purchase_amount)) {
                $vendor->total_purchase_amount = 0;
            }
            if (is_null($vendor->outstanding_balance)) {
                $vendor->outstanding_balance = 0;
            }
        });
    }

    public static function generateVendorCode()
    {
        $prefix = 'VND';
        
        $lastVendor = static::where('vendor_code', 'like', $prefix . '%')
                           ->orderBy('vendor_code', 'desc')
                           ->first();

        if ($lastVendor) {
            $lastNumber = (int) substr($lastVendor->vendor_code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
}
