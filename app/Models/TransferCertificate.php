<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferCertificate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transfer_certificates';

    protected $fillable = [
        'student_id',
        'tc_number',
        'issue_date',
        'reason',
        'from_school',
        'to_school',
        'approved_by',
        'status',
        'file_path',
        'meta',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'meta' => 'array',
    ];

    protected $dates = [
        'issue_date', 'created_at', 'updated_at', 'deleted_at'
    ];

    const STATUS_ISSUED = 'issued';
    const STATUS_REVOKED = 'revoked';

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
