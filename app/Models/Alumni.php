<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alumni extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alumni';

    protected $fillable = [
        'student_id',
        'batch_id',
        'name',
        'admission_no',
        'pass_year',
        'leaving_reason',
        'current_status', // employed, studying, business, other
        'email',
        'phone',
        'linkedin_url',
        'job_title',
        'company',
        'industry',
        'location_city',
        'location_state',
        'location_country',
        'bio',
        'achievements_count',
        'contributions_total',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
        'pass_year' => 'integer',
        'achievements_count' => 'integer',
        'contributions_total' => 'decimal:2',
    ];

    protected $dates = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function batch()
    {
        return $this->belongsTo(AlumniBatch::class, 'batch_id');
    }

    public function achievements()
    {
        return $this->hasMany(AlumniAchievement::class);
    }

    public function contributions()
    {
        return $this->hasMany(AlumniContribution::class);
    }
}
