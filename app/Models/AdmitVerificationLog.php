<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmitVerificationLog extends Model
{
    use HasFactory;

    protected $table = 'verification_logs';

    protected $fillable = [
        'admit_card_id','exam_id','student_id','method','success','scanned_at','verified_by','location','payload','notes'
    ];

    protected $casts = [
        'success' => 'boolean',
        'scanned_at' => 'datetime',
        'payload' => 'array',
    ];

    public function admitCard() { return $this->belongsTo(AdmitCard::class); }
    public function exam() { return $this->belongsTo(Exam::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function verifiedBy() { return $this->belongsTo(User::class, 'verified_by'); }
}