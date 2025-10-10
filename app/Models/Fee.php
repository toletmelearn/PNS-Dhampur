<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'fee_type',
        'amount',
        'due_date',
        'academic_year',
        'month',
        'late_fee',
        'discount',
        'paid_amount',
        'paid_date',
        'status',
        'remarks'
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function student() 
    { 
        return $this->belongsTo(Student::class); 
    }

    public function payments()
    {
        return $this->hasMany(FeePayment::class);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->calculateDueAmount() - $this->paid_amount;
    }

    /**
     * Calculate the total due amount including late fees and discounts
     * 
     * @return float
     */
    public function calculateDueAmount()
    {
        $baseAmount = $this->amount;
        $lateFee = $this->late_fee ?? 0;
        $discount = $this->discount ?? 0;
        
        // Calculate final due amount: base amount + late fees - discounts
        $dueAmount = $baseAmount + $lateFee - $discount;
        
        // Ensure due amount is never negative
        return max(0, $dueAmount);
    }

    public function getIsOverdueAttribute()
    {
        return $this->due_date < now() && $this->status !== 'paid';
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'paid' => 'success',
            'partial' => 'warning',
            'unpaid' => 'danger',
            'overdue' => 'dark'
        ];

        $status = $this->is_overdue && $this->status !== 'paid' ? 'overdue' : $this->status;
        return $badges[$status] ?? 'secondary';
    }
}
