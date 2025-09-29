<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','admission_no','father_name','mother_name','dob','aadhaar','class_id','documents','status'
    ];

    protected $casts = ['documents'=>'array'];

    public function user() { return $this->belongsTo(User::class); }
    public function class() { return $this->belongsTo(ClassModel::class, 'class_id'); }
    public function attendances() { return $this->hasMany(Attendance::class); }
    public function fees() { return $this->hasMany(Fee::class); }
    public function results() { return $this->hasMany(Result::class); }
}
