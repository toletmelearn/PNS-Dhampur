<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;

    protected $fillable = ['student_id','exam_id','subject','marks_obtained','total_marks','grade','uploaded_by'];

    public function student() { return $this->belongsTo(Student::class); }
    public function exam() { return $this->belongsTo(Exam::class); }
    public function uploadedBy() { return $this->belongsTo(User::class,'uploaded_by'); }
}
