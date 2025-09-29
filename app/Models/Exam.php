<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = ['name','class_id','start_date','end_date'];

    public function class() { return $this->belongsTo(ClassModel::class,'class_id'); }
    public function results() { return $this->hasMany(Result::class); }
}
