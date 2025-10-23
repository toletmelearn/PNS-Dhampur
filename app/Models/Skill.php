<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
    ];

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'skill_teacher')
            ->withPivot(['proficiency_level', 'years_experience', 'verified', 'endorsements_count', 'notes'])
            ->withTimestamps();
    }
}