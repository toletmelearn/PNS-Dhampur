<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['student_id','class_id','date','status','marked_by'];

    public function student() { return $this->belongsTo(Student::class); }
    public function class() { return $this->belongsTo(ClassModel::class, 'class_id'); }
    public function markedBy() { return $this->belongsTo(User::class, 'marked_by'); }
}
