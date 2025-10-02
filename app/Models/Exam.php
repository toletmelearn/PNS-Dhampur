<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'class_id',
        'date',
        'time',
        'description',
        'status',
        'start_date', // Keep for backward compatibility
        'end_date'    // Keep for backward compatibility
    ];

    protected $casts = [
        'date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function class() 
    { 
        return $this->belongsTo(ClassModel::class, 'class_id'); 
    }
    
    public function results() 
    { 
        return $this->hasMany(Result::class); 
    }
}
