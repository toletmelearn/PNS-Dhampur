<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BellSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'season',
        'special_schedule_id',
        'source',
        'effective_timings',
    ];

    protected $casts = [
        'date' => 'date',
        'effective_timings' => 'array',
    ];

    public function specialSchedule()
    {
        return $this->belongsTo(SpecialSchedule::class);
    }
}
