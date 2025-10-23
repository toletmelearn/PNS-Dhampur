<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'fee_structure_id',
        'installment_no',
        'item_name',
        'amount',
        'due_date',
        'status',
        'late_fee',
        'discount',
        'paid_amount',
        'paid_date',
        'receipt_id',
        'academic_year',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'datetime',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function structure()
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function transactions()
    {
        return $this->hasMany(FeeTransaction::class);
    }
}