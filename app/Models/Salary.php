<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = ['teacher_id','month','year','basic','allowances','deductions','net_salary','paid_date'];

    protected $casts = ['allowances'=>'array','deductions'=>'array'];

    public function teacher() { return $this->belongsTo(Teacher::class); }
}
