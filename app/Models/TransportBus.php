<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportBus extends Model
{
    use HasFactory;

    protected $table = 'transport_buses';

    protected $fillable = [
        'name', 'number_plate', 'driver_name', 'driver_phone', 'route_name'
    ];

    public function locations()
    {
        return $this->hasMany(TransportBusLocation::class, 'bus_id');
    }

    public function latestLocation()
    {
        return $this->hasOne(TransportBusLocation::class, 'bus_id')->latestOfMany();
    }
}
