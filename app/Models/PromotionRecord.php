<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promotion_records';

    protected $fillable = [
        'student_id',
        'from_class',
        'to_class',
        'academic_year',
        'promotion_date',
        'promoted_by',
        'remarks',
        'status',
    ];

    protected $casts = [
        'promotion_date' => 'date',
    ];

    protected $dates = [
        'promotion_date', 'created_at', 'updated_at', 'deleted_at'
    ];

    const STATUS_RECORDED = 'recorded';
    const STATUS_APPROVED = 'approved';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function promotedBy()
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }
}
