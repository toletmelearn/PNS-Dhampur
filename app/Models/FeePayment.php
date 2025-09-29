<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_id',
        'amount_paid',
        'payment_date',
        'payment_mode', // cash, online, card
        'receipt_no',
        'remarks'
    ];

    protected $casts = [
        'payment_date' => 'datetime',
    ];

    // Relationships
    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }

    public function student()
    {
        return $this->belongsToThrough(Student::class, Fee::class, 'fee_id');
    }
}
