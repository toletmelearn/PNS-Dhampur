<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSeatAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id', 'class_id', 'student_id', 'center_name', 'room_no', 'seat_number', 'roll_number'
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function admitCard()
    {
        return $this->hasOne(AdmitCard::class, 'seat_allocation_id');
    }
}