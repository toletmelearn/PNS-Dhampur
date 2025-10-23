<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatisticalReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'statistical_reports';

    protected $fillable = [
        'context',
        'parameters',
        'metrics',
        'generated_at',
        'generated_by',
        'cache_key',
        'expires_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'metrics' => 'array',
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $dates = [
        'generated_at', 'expires_at', 'created_at', 'updated_at', 'deleted_at'
    ];

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
