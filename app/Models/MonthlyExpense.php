<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MonthlyExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'year',
        'month',
        'department',
        'category',
        'amount',
        'transaction_count',
        'snapshot_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'snapshot_at' => 'datetime',
    ];

    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    public function scopeForDepartment($query, $department)
    {
        return $query->where('department', $department);
    }
}