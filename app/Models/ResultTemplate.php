<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'format',
        'settings',
        'grading_system_id',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function gradingSystem()
    {
        return $this->belongsTo(GradingSystem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSetting(string $key, $default = null)
    {
        $settings = $this->settings ?? [];
        return data_get($settings, $key, $default);
    }
}