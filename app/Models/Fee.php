<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;

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
        return $this->amount - $this->paid_amount;
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
