<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BudgetReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'year',
        'department',
        'type', // utilization, variance, forecast
        'data', // JSON payload
        'generated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'generated_at' => 'datetime',
    ];
}