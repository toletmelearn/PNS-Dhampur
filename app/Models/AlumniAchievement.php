<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlumniAchievement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni_achievements';

    protected $fillable = [
        'alumni_id',
        'title',
        'description',
        'achieved_on',
        'category',
        'url',
        'created_by',
    ];

    protected $casts = [
        'achieved_on' => 'date',
    ];

    protected $dates = [
        'achieved_on', 'created_at', 'updated_at', 'deleted_at'
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
