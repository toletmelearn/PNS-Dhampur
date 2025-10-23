<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'exam_id', 'subject_id', 'class_id',
        'marks_obtained', 'total_marks', 'grade', 'grade_point',
        'uploaded_by', 'template_id', 'status', 'remarks'
    ];

    protected $casts = [
        'marks_obtained' => 'float',
        'total_marks' => 'float',
        'grade_point' => 'float',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function exam() { return $this->belongsTo(Exam::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function classModel() { return $this->belongsTo(ClassModel::class, 'class_id'); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function template() { return $this->belongsTo(ResultTemplate::class, 'template_id'); }
}