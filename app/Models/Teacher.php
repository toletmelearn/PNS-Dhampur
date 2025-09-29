<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','qualification','experience_years','salary','joining_date','documents'
    ];

    protected $casts = ['documents'=>'array'];

    public function user() { return $this->belongsTo(User::class); }
    public function classes() { return $this->hasMany(ClassModel::class, 'class_teacher_id'); }
    public function salaries() { return $this->hasMany(Salary::class); }
    public function syllabus() { return $this->hasMany(Syllabus::class); }
}
