<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlumniEventRegistration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni_event_registrations';

    protected $fillable = [
        'event_id',
        'alumni_id',
        'name',
        'email',
        'phone',
        'checked_in',
        'meta',
    ];

    protected $casts = [
        'checked_in' => 'boolean',
        'meta' => 'array',
    ];

    public function event()
    {
        return $this->belongsTo(AlumniEvent::class, 'event_id');
    }

    public function alumni()
    {
        return $this->belongsTo(Alumni::class, 'alumni_id');
    }
}
