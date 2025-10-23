<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_fee_id',
        'fee_transaction_id',
        'receipt_number',
        'pdf_path',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function studentFee()
    {
        return $this->belongsTo(StudentFee::class);
    }

    public function transaction()
    {
        return $this->belongsTo(FeeTransaction::class, 'fee_transaction_id');
    }
}