<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['teacher_id','month','year','basic','allowances','deductions','net_salary','paid_date'];

    protected $casts = ['allowances'=>'array','deductions'=>'array'];

    public function teacher() { return $this->belongsTo(Teacher::class); }
}
