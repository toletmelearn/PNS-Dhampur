<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlumniEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni_events';

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'location',
        'status', // draft, published, archived
        'registration_url',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'meta' => 'array',
    ];

    protected $dates = [
        'start_date', 'end_date', 'created_at', 'updated_at', 'deleted_at'
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
