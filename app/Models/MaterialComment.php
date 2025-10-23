<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'material_comments';

    protected $fillable = [
        'material_id',
        'user_id',
        'parent_id',
        'comment',
        'is_resolved',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function material()
    {
        return $this->belongsTo(SubjectMaterial::class, 'material_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(MaterialComment::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(MaterialComment::class, 'parent_id');
    }
}
