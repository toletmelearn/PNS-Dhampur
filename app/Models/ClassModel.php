<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    use HasFactory;

    protected $table = 'class_models';
    protected $fillable = [
        'name',
        'section',
        'class_teacher_id',
        'description',
        'capacity',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer'
    ];

    // Relationships
    public function students() 
    { 
        return $this->hasMany(Student::class, 'class_id'); 
    }
    
    public function teacher() 
    { 
        return $this->belongsTo(Teacher::class, 'class_teacher_id'); 
    }
    
    public function classTeacher() 
    { 
        return $this->belongsTo(Teacher::class, 'class_teacher_id'); 
    }
    
    public function exams() 
    { 
        return $this->hasMany(Exam::class, 'class_id'); 
    }
    
    public function subjects() 
    { 
        return $this->hasMany(Subject::class, 'class_id'); 
    }
    
    public function srRegisters() 
    { 
        return $this->hasMany(SRRegister::class, 'class_id'); 
    }
    
    public function syllabi() 
    { 
        return $this->hasMany(Syllabus::class, 'class_id'); 
    }
}
