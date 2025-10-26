<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_model_id',
        'name',
        'academic_year',
        'description',
        'is_active',
    ];

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class);
    }

    public function items()
    {
        return $this->hasMany(FeeStructureItem::class);
    }

    public function studentFees()
    {
        return $this->hasMany(StudentFee::class);
    }
}