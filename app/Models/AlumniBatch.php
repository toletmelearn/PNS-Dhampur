<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlumniBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni_batches';

    protected $fillable = [
        'label',
        'year_start',
        'year_end',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'year_start' => 'integer',
        'year_end' => 'integer',
    ];

    protected $dates = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    public function alumni()
    {
        return $this->hasMany(Alumni::class, 'batch_id');
    }
}
