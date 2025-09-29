<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'classes';
    protected $fillable = ['name','section','class_teacher_id'];

    public function students() { return $this->hasMany(Student::class, 'class_id'); }
    public function teacher() { return $this->belongsTo(Teacher::class, 'class_teacher_id'); }
    public function exams() { return $this->hasMany(Exam::class, 'class_id'); }
}
