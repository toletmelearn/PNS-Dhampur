<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportBusLocation extends Model
{
    use HasFactory;

    protected $table = 'transport_bus_locations';

    protected $fillable = [
        'bus_id','latitude','longitude','recorded_at'
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function bus()
    {
        return $this->belongsTo(TransportBus::class, 'bus_id');
    }
}
