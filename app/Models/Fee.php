<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;

    protected $fillable = ['student_id','amount','due_date','paid_amount','paid_date','status','remarks'];

    public function student() { return $this->belongsTo(Student::class); }
}
