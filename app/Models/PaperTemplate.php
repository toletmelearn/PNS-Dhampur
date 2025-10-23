<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaperTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'file_path', 'mime_type', 'created_by', 'updated_by', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function papers()
    {
        return $this->hasMany(ExamPaper::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}