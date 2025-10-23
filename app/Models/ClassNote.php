<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'class_notes';

    protected $fillable = [
        'class_model_id',
        'user_id',
        'note',
        'visibility',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function classModel()
    {
        return $this->belongsTo(ClassModel::class, 'class_model_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function changeLogs()
    {
        return $this->morphMany(ChangeLog::class, 'changeable');
    }
}
