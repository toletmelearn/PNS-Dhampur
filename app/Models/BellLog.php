<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BellLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bell_timing_id',
        'special_schedule_id',
        'schedule_type',
        'ring_type',
        'name',
        'time',
        'season',
        'date',
        'suppressed',
        'forced',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'time' => 'datetime:H:i',
        'date' => 'date',
        'suppressed' => 'boolean',
        'forced' => 'boolean',
        'metadata' => 'array',
    ];

    public function bellTiming()
    {
        return $this->belongsTo(BellTiming::class);
    }

    public function specialSchedule()
    {
        return $this->belongsTo(SpecialSchedule::class);
    }
}
