<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'student_id',
        'date',
        'status',
        'marked_by',
        'remarks',
        'academic_year'
    ];
    
    protected $casts = [
        'date' => 'date'
    ];
    
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
