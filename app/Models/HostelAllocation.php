<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostelAllocation extends Model
{
    use HasFactory;

    protected $table = 'hostel_allocations';

    protected $fillable = [
        'room_id', 'student_id', 'allocated_at', 'vacated_at', 'status'
    ];

    protected $casts = [
        'allocated_at' => 'datetime',
        'vacated_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
