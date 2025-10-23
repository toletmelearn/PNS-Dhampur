<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostelBuilding extends Model
{
    use HasFactory;

    protected $table = 'hostel_buildings';

    protected $fillable = [
        'name', 'warden_name', 'gender', 'capacity'
    ];

    public function rooms()
    {
        return $this->hasMany(HostelRoom::class, 'building_id');
    }
}
