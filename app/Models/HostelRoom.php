<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostelRoom extends Model
{
    use HasFactory;

    protected $table = 'hostel_rooms';

    protected $fillable = [
        'building_id', 'room_number', 'bed_count', 'gender', 'status'
    ];

    public function building()
    {
        return $this->belongsTo(HostelBuilding::class, 'building_id');
    }

    public function allocations()
    {
        return $this->hasMany(HostelAllocation::class, 'room_id');
    }

    public function activeAllocations()
    {
        return $this->hasMany(HostelAllocation::class, 'room_id')->whereNull('vacated_at');
    }
}
