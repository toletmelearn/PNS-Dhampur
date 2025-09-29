<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Syllabus extends Model
{
    use HasFactory;

    protected $table = 'syllabus';
    protected $fillable = ['class_id','subject','teacher_id','file_path','note'];

    public function class() { return $this->belongsTo(ClassModel::class,'class_id'); }
    public function teacher() { return $this->belongsTo(Teacher::class); }
}
