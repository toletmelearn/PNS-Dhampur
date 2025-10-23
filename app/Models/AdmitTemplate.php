<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmitTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'format', 'settings', 'is_active', 'created_by'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function admitCards()
    {
        return $this->hasMany(AdmitCard::class, 'template_id');
    }
}