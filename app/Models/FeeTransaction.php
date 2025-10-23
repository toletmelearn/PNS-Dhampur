<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_fee_id',
        'amount',
        'transaction_id',
        'gateway',
        'status',
        'payment_method',
        'paid_at',
        'metadata',
        'receipt_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function studentFee()
    {
        return $this->belongsTo(StudentFee::class);
    }

    public function receipt()
    {
        return $this->belongsTo(FeeReceipt::class, 'receipt_id');
    }
}