<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnnualBudget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'year',
        'total_allocated',
        'total_spent',
        'status', // planned, approved, locked
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'total_allocated' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function departmentBudgets()
    {
        return $this->hasMany(DepartmentBudget::class, 'budget_year', 'year');
    }

    public function getUtilizationAttribute()
    {
        $allocated = $this->total_allocated ?? $this->departmentBudgets()->sum('allocated_budget');
        $spent = $this->total_spent ?? $this->departmentBudgets()->sum('spent_amount');
        return $allocated > 0 ? round(($spent / $allocated) * 100, 2) : 0;
    }
}