<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AlumniContribution extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni_contributions';

    protected $fillable = [
        'alumni_id',
        'type', // donation, volunteer, sponsorship
        'amount',
        'currency',
        'contribution_date',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'contribution_date' => 'date',
    ];

    protected $dates = [
        'contribution_date', 'created_at', 'updated_at', 'deleted_at'
    ];

    public function alumni()
    {
        return $this->belongsTo(Alumni::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
