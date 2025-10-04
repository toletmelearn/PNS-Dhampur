<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_number',
        'type', // 'expense', 'income'
        'category',
        'subcategory',
        'amount',
        'description',
        'department',
        'budget_id',
        'vendor_id',
        'purchase_order_id',
        'reference_number',
        'transaction_date',
        'payment_method',
        'status', // 'pending', 'approved', 'completed', 'cancelled'
        'approved_by',
        'approved_at',
        'notes',
        'attachments',
        'tax_amount',
        'discount_amount',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'transaction_date' => 'date',
        'approved_at' => 'datetime',
        'attachments' => 'array'
    ];

    protected $dates = [
        'transaction_date',
        'approved_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Relationships
    public function budget()
    {
        return $this->belongsTo(Budget::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeExpenses($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('transaction_date', now()->month)
                    ->whereYear('transaction_date', now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('transaction_date', now()->year);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getNetAmountAttribute()
    {
        return $this->amount - $this->discount_amount + $this->tax_amount;
    }

    public function getFormattedAmountAttribute()
    {
        return '₹' . number_format($this->amount, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'warning',
            'approved' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger'
        ];

        return $badges[$this->status] ?? 'secondary';
    }

    // Mutators
    public function setTransactionNumberAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['transaction_number'] = $this->generateTransactionNumber();
        } else {
            $this->attributes['transaction_number'] = $value;
        }
    }

    // Helper methods
    public function generateTransactionNumber()
    {
        $prefix = $this->type === 'expense' ? 'EXP' : 'INC';
        $year = now()->year;
        $month = now()->format('m');
        
        $lastTransaction = static::where('type', $this->type)
                                ->whereYear('created_at', $year)
                                ->whereMonth('created_at', now()->month)
                                ->orderBy('id', 'desc')
                                ->first();
        
        $sequence = $lastTransaction ? 
            (intval(substr($lastTransaction->transaction_number, -4)) + 1) : 1;
        
        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    public function canBeDeleted()
    {
        return $this->status === 'pending';
    }

    public function approve($userId)
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now()
        ]);

        // Update budget spent amount if linked to budget
        if ($this->budget_id && $this->type === 'expense') {
            $this->budget->increment('spent_amount', $this->net_amount);
        }
    }

    public function complete()
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);

        // Reverse budget spent amount if it was approved
        if ($this->budget_id && $this->type === 'expense' && $this->status === 'approved') {
            $this->budget->decrement('spent_amount', $this->net_amount);
        }
    }

    // Static methods
    public static function getTotalExpenses($startDate = null, $endDate = null, $department = null)
    {
        $query = static::expenses()->completed();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        if ($department) {
            $query->byDepartment($department);
        }
        
        return $query->sum('amount');
    }

    public static function getTotalIncome($startDate = null, $endDate = null, $department = null)
    {
        $query = static::income()->completed();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        if ($department) {
            $query->byDepartment($department);
        }
        
        return $query->sum('amount');
    }

    public static function getMonthlyExpenses($year = null, $department = null)
    {
        $year = $year ?? now()->year;
        
        $query = static::expenses()
                      ->completed()
                      ->whereYear('transaction_date', $year);
        
        if ($department) {
            $query->byDepartment($department);
        }
        
        return $query->selectRaw('MONTH(transaction_date) as month, SUM(amount) as total')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->keyBy('month');
    }

    public static function getCategoryExpenses($startDate = null, $endDate = null, $department = null)
    {
        $query = static::expenses()->completed();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        if ($department) {
            $query->byDepartment($department);
        }
        
        return $query->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
                    ->groupBy('category')
                    ->orderByDesc('total')
                    ->get();
    }

    public static function getDepartmentExpenses($startDate = null, $endDate = null)
    {
        $query = static::expenses()->completed();
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }
        
        return $query->selectRaw('department, SUM(amount) as total, COUNT(*) as count')
                    ->groupBy('department')
                    ->orderByDesc('total')
                    ->get();
    }
}
