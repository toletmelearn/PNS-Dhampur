<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_id',
        'name',
        'teacher_id',
        'capacity',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity' => 'integer'
    ];

    protected $attributes = [
        'is_active' => true
    ];

    // Relationships
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'section_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return $this->class->name . ' - ' . $this->name;
    }

    // Helper methods
    public function isAtCapacity()
    {
        return $this->students()->count() >= $this->capacity;
    }

    public function getAvailableSeats()
    {
        return max(0, $this->capacity - $this->students()->count());
    }
}